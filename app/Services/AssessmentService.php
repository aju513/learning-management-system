<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\AttemptStatus;
use App\Enums\MaterialType;
use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseRepositoryInterface $courses,
        private readonly UserRepositoryInterface $users,
        private readonly LearningService $learning,
    ) {}

    public function create(array $data, User $actor): Assessment
    {
        $data['created_by'] = $actor->id;
        $data['status'] = AssessmentStatus::Draft;
        $data = $this->normalizeCourseLink($data, $actor);
        $assessment = $this->assessments->create($data);
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment.created')->log('Assessment created');

        return $assessment;
    }

    public function update(Assessment $assessment, array $data, User $actor): Assessment
    {
        if ($this->assessments->hasAttempts($assessment)) {
            unset($data['course_id'], $data['course_module_id']);
        } else {
            $data = $this->normalizeCourseLink($data, $actor);
        }
        $assessment = $this->assessments->update($assessment, $data);
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment.updated')->log('Assessment updated');

        return $assessment;
    }

    public function changeStatus(Assessment $assessment, AssessmentStatus $status, User $actor): Assessment
    {
        if ($status === AssessmentStatus::Published) {
            $assessment = $this->assessments->findForManagement($assessment);
            if ($assessment->questions->isEmpty()) {
                throw ValidationException::withMessages(['status' => 'Add at least one question before publishing.']);
            }
            foreach ($assessment->questions as $question) {
                $correctCount = $question->options->where('is_correct', true)->count();
                if ($question->options->count() < 2 || $correctCount < 1) {
                    throw ValidationException::withMessages(['status' => 'Every question needs at least two options and a correct answer.']);
                }
                if ($question->type !== QuestionType::MultipleChoice && $correctCount !== 1) {
                    throw ValidationException::withMessages(['status' => 'Single-choice and true/false questions need exactly one correct answer.']);
                }
            }
        }
        $assessment = $this->assessments->update($assessment, ['status' => $status]);
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment.status-changed')
            ->withProperties(['status' => $status->value])->log('Assessment status changed');

        return $assessment;
    }

    public function delete(Assessment $assessment, User $actor): void
    {
        if ($this->assessments->hasAttempts($assessment)) {
            throw ValidationException::withMessages(['assessment' => 'Close assessments with attempt history instead of deleting them.']);
        }
        if ($this->assessments->hasAttachedMaterials($assessment)) {
            throw ValidationException::withMessages(['assessment' => 'Detach this assessment from learning materials before deleting it.']);
        }
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment.deleted')
            ->withProperties(['title' => $assessment->title])->log('Assessment deleted');
        $this->assessments->delete($assessment);
    }

    public function createQuestion(Assessment $assessment, array $data, User $actor): AssessmentQuestion
    {
        if ($this->assessments->hasAttempts($assessment)) {
            throw ValidationException::withMessages(['question' => 'Questions cannot change after attempts have started.']);
        }

        return DB::transaction(function () use ($assessment, $data, $actor): AssessmentQuestion {
            $options = $this->optionsFromData($data);
            unset($data['options'], $data['correct_options']);
            $question = $this->assessments->createQuestion([
                ...$data,
                'assessment_id' => $assessment->id,
                'position' => $this->assessments->nextQuestionPosition($assessment),
            ]);
            $this->assessments->replaceOptions($question, $options);
            activity('lms')->causedBy($actor)->performedOn($question)->event('assessment-question.created')
                ->withProperties(['assessment_id' => $assessment->id])->log('Assessment question created');

            return $question;
        });
    }

    public function updateQuestion(AssessmentQuestion $question, array $data, User $actor): AssessmentQuestion
    {
        if ($this->assessments->hasAttempts($question->assessment)) {
            throw ValidationException::withMessages(['question' => 'Questions cannot change after attempts have started.']);
        }

        return DB::transaction(function () use ($question, $data, $actor): AssessmentQuestion {
            $options = $this->optionsFromData($data);
            unset($data['options'], $data['correct_options']);
            $question = $this->assessments->updateQuestion($question, $data);
            $this->assessments->replaceOptions($question, $options);
            activity('lms')->causedBy($actor)->performedOn($question)->event('assessment-question.updated')->log('Assessment question updated');

            return $question;
        });
    }

    public function deleteQuestion(AssessmentQuestion $question, User $actor): void
    {
        if ($this->assessments->hasAttempts($question->assessment)) {
            throw ValidationException::withMessages(['question' => 'Questions cannot change after attempts have started.']);
        }

        activity('lms')->causedBy($actor)->performedOn($question)->event('assessment-question.deleted')
            ->withProperties(['assessment_id' => $question->assessment_id])->log('Assessment question deleted');
        $this->assessments->deleteQuestion($question);
    }

    public function assign(Assessment $assessment, array $traineeIds, ?string $dueAt, User $actor): void
    {
        DB::transaction(function () use ($assessment, $traineeIds, $dueAt, $actor): void {
            $trainees = $this->users->findByIds($traineeIds);
            $eligibleIds = $this->enrollments->traineesForAssessmentAssignment($actor)->pluck('id');
            if ($trainees->count() !== count(array_unique($traineeIds))) {
                throw ValidationException::withMessages(['trainees' => 'One or more selected trainees could not be found.']);
            }
            foreach ($trainees as $trainee) {
                if (! $eligibleIds->contains($trainee->id)) {
                    throw ValidationException::withMessages(['trainees' => "{$trainee->name} is not an eligible trainee for this assignment."]);
                }
                $assignment = $this->assessments->assign($assessment, $trainee, $actor, $dueAt);
                activity('lms')->causedBy($actor)->performedOn($assignment)->event('assessment.assigned')
                    ->withProperties(['assessment_id' => $assessment->id, 'trainee_id' => $trainee->id])->log('Assessment assigned');
            }
        });
    }

    public function unassign(AssessmentAssignment $assignment, User $actor): void
    {
        activity('lms')->causedBy($actor)->performedOn($assignment)->event('assessment.unassigned')
            ->withProperties(['assessment_id' => $assignment->assessment_id, 'trainee_id' => $assignment->user_id])->log('Assessment unassigned');
        $this->assessments->unassign($assignment);
    }

    public function start(Assessment $assessment, User $trainee): AssessmentAttempt
    {
        $assessment = $this->assessments->findForAvailability($assessment);
        if (! $assessment->isAvailable() || ! $this->assessments->userCanTake($assessment, $trainee)) {
            throw new AuthorizationException('This assessment is not currently available to you.');
        }
        if ($active = $this->assessments->activeAttempt($assessment, $trainee)) {
            return $active;
        }
        $attemptCount = $this->assessments->countAttempts($assessment, $trainee);
        if ($attemptCount >= $assessment->max_attempts) {
            throw ValidationException::withMessages(['attempt' => 'You have used all allowed attempts.']);
        }
        $attempt = $this->assessments->createAttempt([
            'assessment_id' => $assessment->id,
            'user_id' => $trainee->id,
            'attempt_number' => $attemptCount + 1,
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
            'expires_at' => now()->addMinutes($assessment->duration_minutes),
        ]);
        activity('lms')->causedBy($trainee)->performedOn($attempt)->event('assessment-attempt.started')
            ->withProperties(['assessment_id' => $assessment->id, 'attempt_number' => $attempt->attempt_number])->log('Assessment attempt started');

        return $attempt;
    }

    public function submit(AssessmentAttempt $attempt, array $answers, User $trainee): AssessmentAttempt
    {
        if ($attempt->status !== AttemptStatus::InProgress || $attempt->user_id !== $trainee->id) {
            throw new AuthorizationException('This attempt cannot be submitted.');
        }

        return DB::transaction(function () use ($attempt, $answers, $trainee): AssessmentAttempt {
            $attempt = $this->assessments->findAttemptForTaking($attempt);
            $earned = 0.0;
            $total = (float) $attempt->assessment->questions->sum('marks');
            foreach ($attempt->assessment->questions as $question) {
                $selected = array_values(array_unique(array_map('intval', Arr::wrap($answers[$question->id] ?? []))));
                sort($selected);
                $validOptionIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();
                $selected = array_values(array_intersect($selected, $validOptionIds));
                $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $isCorrect = $selected === $correct;
                $marks = $isCorrect ? (float) $question->marks : 0.0;
                $earned += $marks;
                $this->assessments->createAnswer([
                    'assessment_attempt_id' => $attempt->id,
                    'assessment_question_id' => $question->id,
                    'selected_option_ids' => $selected,
                    'earned_marks' => $marks,
                    'is_correct' => $isCorrect,
                ]);
            }
            $score = $total > 0 ? round($earned / $total * 100, 2) : 0;
            $attempt = $this->assessments->updateAttempt($attempt, [
                'status' => AttemptStatus::Graded,
                'submitted_at' => now(),
                'earned_marks' => $earned,
                'total_marks' => $total,
                'score_percentage' => $score,
                'passed' => $score >= (float) $attempt->assessment->passing_percentage,
            ]);
            activity('lms')->causedBy($trainee)->performedOn($attempt)->event('assessment-attempt.graded')
                ->withProperties(['assessment_id' => $attempt->assessment_id, 'score' => $score, 'passed' => $attempt->passed])->log('Assessment attempt graded');

            if ($attempt->passed) {
                $this->completeAttachedMaterials($attempt->assessment, $trainee);
            }

            return $attempt;
        });
    }

    private function optionsFromData(array $data): array
    {
        $correct = array_map('intval', $data['correct_options'] ?? []);

        return collect($data['options'])->values()->map(fn (string $text, int $index) => [
            'option_text' => $text,
            'is_correct' => in_array($index, $correct, true),
            'position' => $index + 1,
        ])->all();
    }

    private function normalizeCourseLink(array $data, User $actor): array
    {
        if (filled($data['course_module_id'] ?? null)) {
            $module = $this->courses->findModule((int) $data['course_module_id']);
            if (! $actor->can('courses.edit-any') && $module->course->instructor_id !== $actor->id) {
                throw ValidationException::withMessages(['course_module_id' => 'You can only link assessments to your own courses.']);
            }
            $data['course_id'] = $module->course_id;
        } elseif (filled($data['course_id'] ?? null)) {
            $course = $this->courses->findCourse((int) $data['course_id']);
            if (! $actor->can('courses.edit-any') && $course->instructor_id !== $actor->id) {
                throw ValidationException::withMessages(['course_id' => 'You can only link assessments to your own courses.']);
            }
        }

        return $data;
    }

    private function completeAttachedMaterials(Assessment $assessment, User $trainee): void
    {
        foreach ($this->assessments->materialsFor($assessment) as $material) {
            if ($material->type !== MaterialType::Assessment) {
                continue;
            }
            $enrollment = $this->enrollments->findForCourseAndTrainee($material->module->course, $trainee);
            if ($enrollment) {
                $this->enrollments->completeMaterial($enrollment, $material);
                $this->learning->recalculate($enrollment);
            }
        }
    }
}
