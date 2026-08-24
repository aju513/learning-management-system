<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly UserRepositoryInterface $users,
        private readonly CreditScoreService $credits,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function create(array $data, User $actor): Assessment
    {
        unset($data['available_to_all']);
        $data['created_by'] = $actor->id;
        $data['status'] = AssessmentStatus::Draft;
        $assessment = $this->assessments->create($data);
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment.created')->log('Assessment created');

        return $assessment;
    }

    public function update(Assessment $assessment, array $data, User $actor): Assessment
    {
        unset($data['available_to_all']);
        if ($assessment->questions()->where('type', QuestionType::QuestionAnswer->value)->exists()) {
            $data['show_results'] = true;
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
                if ($question->type === QuestionType::QuestionAnswer) {
                    if (blank($question->reference_answer)) {
                        throw ValidationException::withMessages(['status' => 'Every question-and-answer item needs a reference answer.']);
                    }

                    continue;
                }
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
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment.deleted')
            ->withProperties(['title' => $assessment->title])->log('Assessment deleted');
        $this->assessments->delete($assessment);
    }

    public function createQuestion(Assessment $assessment, array $data, User $actor): AssessmentQuestion
    {
        $this->ensureEditable($assessment);

        return DB::transaction(function () use ($assessment, $data, $actor): AssessmentQuestion {
            $options = $this->optionsFromData($data);
            unset($data['options'], $data['correct_options']);
            $question = $this->assessments->createQuestion([
                ...$data,
                'assessment_id' => $assessment->id,
                'position' => $this->assessments->nextQuestionPosition($assessment),
            ]);
            $this->assessments->replaceOptions($question, $options);
            if ($question->type === QuestionType::QuestionAnswer) {
                $this->assessments->update($assessment, ['show_results' => true]);
            }
            activity('lms')->causedBy($actor)->performedOn($question)->event('assessment-question.created')
                ->withProperties(['assessment_id' => $assessment->id])->log('Assessment question created');

            return $question;
        });
    }

    public function updateQuestion(AssessmentQuestion $question, array $data, User $actor): AssessmentQuestion
    {
        $this->ensureEditable($question->assessment);

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
        $this->ensureEditable($question->assessment);

        activity('lms')->causedBy($actor)->performedOn($question)->event('assessment-question.deleted')
            ->withProperties(['assessment_id' => $question->assessment_id])->log('Assessment question deleted');
        $this->assessments->deleteQuestion($question);
    }

    public function reorderQuestions(Assessment $assessment, array $questionIds, User $actor): void
    {
        if ($this->assessments->hasAttempts($assessment)) {
            throw ValidationException::withMessages(['question_ids' => 'Questions cannot change after attempts have started.']);
        }
        $existing = $this->assessments->questionIds($assessment);
        $submitted = array_map('intval', $questionIds);
        if (count($existing) !== count($submitted) || array_diff($existing, $submitted) || array_diff($submitted, $existing)) {
            throw ValidationException::withMessages(['question_ids' => 'Submit every question from this quiz exactly once.']);
        }
        DB::transaction(fn () => $this->assessments->reorderQuestions($assessment, $submitted));
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment-questions.reordered')->log('Assessment questions reordered');
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
        if (! $assessment->isAvailable() || ! $this->assessments->userCanTake($assessment, $trainee) || ! $this->availability->isAvailable($assessment, $trainee)) {
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
            'expires_at' => now()->addMinutes((int) $assessment->duration_minutes),
        ]);
        activity('lms')->causedBy($trainee)->performedOn($attempt)->event('assessment-attempt.started')
            ->withProperties(['assessment_id' => $assessment->id, 'attempt_number' => $attempt->attempt_number])->log('Assessment attempt started');

        return $attempt;
    }

    public function submit(AssessmentAttempt $attempt, array $answers, User $trainee): AssessmentAttempt
    {
        if ((int) $attempt->user_id !== (int) $trainee->id) {
            throw new AuthorizationException('This attempt cannot be submitted.');
        }

        if ($attempt->status !== AttemptStatus::InProgress) {
            return $this->assessments->findAttemptForTaking($attempt);
        }

        return DB::transaction(function () use ($attempt, $answers, $trainee): AssessmentAttempt {
            $attempt = $this->assessments->findAttemptForTaking($attempt);
            $earned = 0.0;
            $total = (float) $attempt->assessment->questions->sum('marks');
            foreach ($attempt->assessment->questions as $question) {
                if ($question->type === QuestionType::QuestionAnswer) {
                    $this->assessments->upsertAnswer($attempt, $question, [
                        'assessment_attempt_id' => $attempt->id,
                        'assessment_question_id' => $question->id,
                        'selected_option_ids' => null,
                        'text_answer' => trim((string) ($answers[$question->id] ?? '')),
                        'earned_marks' => 0,
                        'is_correct' => false,
                    ]);

                    continue;
                }
                $selected = array_values(array_unique(array_map('intval', Arr::wrap($answers[$question->id] ?? []))));
                sort($selected);
                $validOptionIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();
                $selected = array_values(array_intersect($selected, $validOptionIds));
                $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $isCorrect = $selected === $correct;
                $marks = $isCorrect ? (float) $question->marks : 0.0;
                $earned += $marks;
                $this->assessments->upsertAnswer($attempt, $question, [
                    'assessment_attempt_id' => $attempt->id,
                    'assessment_question_id' => $question->id,
                    'selected_option_ids' => $selected,
                    'text_answer' => null,
                    'earned_marks' => $marks,
                    'is_correct' => $isCorrect,
                ]);
            }
            $requiresReview = $attempt->assessment->questions->contains(fn ($question) => $question->type === QuestionType::QuestionAnswer);
            $score = $requiresReview ? null : ($total > 0 ? round($earned / $total * 100, 2) : 0);
            $attempt = $this->assessments->updateAttempt($attempt, [
                'status' => $requiresReview ? AttemptStatus::PendingReview : AttemptStatus::Graded,
                'submitted_at' => now(),
                'earned_marks' => $earned,
                'total_marks' => $total,
                'score_percentage' => $score,
                'passed' => $requiresReview ? null : $score >= (float) $attempt->assessment->passing_percentage,
            ]);
            if ($attempt->passed) {
                $this->credits->recordAssessmentPass($attempt->assessment, $trainee, $attempt->submitted_at);
            }
            activity('lms')->causedBy($trainee)->performedOn($attempt)->event($requiresReview ? 'assessment-attempt.submitted' : 'assessment-attempt.graded')
                ->withProperties(['assessment_id' => $attempt->assessment_id, 'status' => $attempt->status->value])->log($requiresReview ? 'Assessment attempt submitted for review' : 'Assessment attempt graded');

            return $attempt;
        });
    }

    public function saveAnswers(AssessmentAttempt $attempt, array $answers, User $trainee): void
    {
        if ($attempt->status !== AttemptStatus::InProgress || (int) $attempt->user_id !== (int) $trainee->id) {
            throw new AuthorizationException('This attempt is no longer available for saving.');
        }

        $attempt = $this->assessments->findAttemptForTaking($attempt);
        foreach ($attempt->assessment->questions as $question) {
            $key = (string) $question->id;
            if (! array_key_exists($key, $answers) && ! array_key_exists($question->id, $answers)) {
                continue;
            }
            $answer = $answers[$key] ?? $answers[$question->id];
            $attributes = $question->type === QuestionType::QuestionAnswer
                ? ['selected_option_ids' => null, 'text_answer' => trim((string) $answer)]
                : ['selected_option_ids' => $this->selectedOptionIds($question, $answer), 'text_answer' => null];

            $this->assessments->upsertAnswer($attempt, $question, [
                ...$attributes,
                'earned_marks' => 0,
                'is_correct' => false,
            ]);
        }
    }

    public function review(AssessmentAttempt $attempt, array $reviews, User $reviewer): AssessmentAttempt
    {
        if ($attempt->status !== AttemptStatus::PendingReview) {
            throw ValidationException::withMessages(['reviews' => 'Only attempts pending review can be graded.']);
        }

        return DB::transaction(function () use ($attempt, $reviews, $reviewer): AssessmentAttempt {
            $attempt = $this->assessments->findAttemptForTaking($attempt);
            $manualAnswers = $attempt->answers->filter(fn ($answer) => $answer->question->type === QuestionType::QuestionAnswer);
            foreach ($manualAnswers as $answer) {
                $review = $reviews[$answer->id] ?? null;
                if (! is_array($review)) {
                    throw ValidationException::withMessages(['reviews' => 'Grade every question-and-answer response.']);
                }
                $marks = (float) ($review['marks'] ?? -1);
                if ($marks < 0 || $marks > (float) $answer->question->marks) {
                    throw ValidationException::withMessages(["reviews.{$answer->id}.marks" => "Marks must be between 0 and {$answer->question->marks}."]);
                }
                $this->assessments->updateAnswer($answer, [
                    'earned_marks' => $marks,
                    'is_correct' => $marks >= (float) $answer->question->marks,
                    'reviewer_feedback' => $review['feedback'] ?? null,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);
            }

            $attempt = $this->assessments->findAttemptForTaking($attempt);
            $earned = (float) $attempt->answers->sum('earned_marks');
            $total = (float) $attempt->assessment->questions->sum('marks');
            $score = $total > 0 ? round($earned / $total * 100, 2) : 0;
            $attempt = $this->assessments->updateAttempt($attempt, [
                'status' => AttemptStatus::Graded,
                'earned_marks' => $earned,
                'total_marks' => $total,
                'score_percentage' => $score,
                'passed' => $score >= (float) $attempt->assessment->passing_percentage,
            ]);
            if ($attempt->passed) {
                $this->credits->recordAssessmentPass($attempt->assessment, $attempt->trainee, $attempt->submitted_at);
            }
            activity('lms')->causedBy($reviewer)->performedOn($attempt)->event('assessment-attempt.reviewed')
                ->withProperties(['score' => $score, 'passed' => $attempt->passed])->log('Assessment attempt manually reviewed');

            return $attempt;
        });
    }

    private function optionsFromData(array $data): array
    {
        $correct = array_map('intval', $data['correct_options'] ?? []);

        return collect($data['options'] ?? [])->values()->map(fn (string $text, int $index) => [
            'option_text' => $text,
            'is_correct' => in_array($index, $correct, true),
            'position' => $index + 1,
        ])->all();
    }

    private function selectedOptionIds(AssessmentQuestion $question, mixed $answer): array
    {
        $selected = array_values(array_unique(array_map('intval', Arr::wrap($answer))));
        sort($selected);

        return array_values(array_intersect($selected, $question->options->pluck('id')->map(fn ($id) => (int) $id)->all()));
    }

    private function ensureEditable(Assessment $assessment): void
    {
        if ($assessment->status === AssessmentStatus::Published) {
            throw ValidationException::withMessages(['question' => 'Published quizzes are locked. Close the quiz before changing its questions.']);
        }
        if ($this->assessments->hasAttempts($assessment)) {
            throw ValidationException::withMessages(['question' => 'Questions cannot change after attempts have started.']);
        }
    }
}
