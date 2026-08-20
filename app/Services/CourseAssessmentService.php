<?php

namespace App\Services;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAttempt;
use App\Models\CourseAssessmentQuestion;
use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Contracts\CourseAssessmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseAssessmentService
{
    public function __construct(
        private readonly CourseAssessmentRepositoryInterface $courseAssessments,
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly LearningService $learning,
        private readonly CreditScoreService $credits,
    ) {}

    public function createQuestion(CourseAssessment $assessment, array $data, User $actor): CourseAssessmentQuestion
    {
        $this->ensureEditable($assessment);

        return DB::transaction(function () use ($assessment, $data, $actor): CourseAssessmentQuestion {
            $options = $this->optionsFromData($data);
            unset($data['options'], $data['correct_options']);
            $question = $this->courseAssessments->createQuestion([
                ...$data,
                'course_assessment_id' => $assessment->id,
                'position' => $this->courseAssessments->nextQuestionPosition($assessment),
            ]);
            $this->courseAssessments->replaceOptions($question, $options);
            activity('lms')->causedBy($actor)->performedOn($question)->event('course-assessment-question.created')
                ->withProperties(['course_assessment_id' => $assessment->id])->log('Course assessment question created');

            return $question;
        });
    }

    public function updateQuestion(CourseAssessmentQuestion $question, array $data, User $actor): CourseAssessmentQuestion
    {
        $this->ensureEditable($question->courseAssessment);

        return DB::transaction(function () use ($question, $data, $actor): CourseAssessmentQuestion {
            $options = $this->optionsFromData($data);
            unset($data['options'], $data['correct_options']);
            $question = $this->courseAssessments->updateQuestion($question, $data);
            $this->courseAssessments->replaceOptions($question, $options);
            activity('lms')->causedBy($actor)->performedOn($question)->event('course-assessment-question.updated')->log('Course assessment question updated');

            return $question;
        });
    }

    public function deleteQuestion(CourseAssessmentQuestion $question, User $actor): void
    {
        $this->ensureEditable($question->courseAssessment);
        activity('lms')->causedBy($actor)->performedOn($question)->event('course-assessment-question.deleted')
            ->withProperties(['course_assessment_id' => $question->course_assessment_id])->log('Course assessment question deleted');
        $this->courseAssessments->deleteQuestion($question);
    }

    public function reorderQuestions(CourseAssessment $assessment, array $questionIds, User $actor): void
    {
        $this->ensureEditable($assessment);
        $existing = $this->courseAssessments->questionIds($assessment);
        $submitted = array_map('intval', $questionIds);
        if (count($existing) !== count($submitted) || array_diff($existing, $submitted) || array_diff($submitted, $existing)) {
            throw ValidationException::withMessages(['question_ids' => 'Submit every course-assessment question exactly once.']);
        }
        DB::transaction(fn () => $this->courseAssessments->reorderQuestions($assessment, $submitted));
        activity('lms')->causedBy($actor)->performedOn($assessment)->event('course-assessment-questions.reordered')->log('Course assessment questions reordered');
    }

    public function start(CourseAssessment $assessment, Enrollment $enrollment, User $trainee): CourseAssessmentAttempt
    {
        $assessment = $this->courseAssessments->findForManagement($assessment);
        if ($enrollment->user_id !== $trainee->id || $assessment->material->chapter->module->course_id !== $enrollment->course_id) {
            throw new AuthorizationException('This course assessment is not part of your enrollment.');
        }
        if ($active = $this->courseAssessments->activeAttempt($assessment, $trainee)) {
            return $active;
        }
        if ($this->courseAssessments->hasPassed($assessment, $trainee)) {
            throw ValidationException::withMessages(['assessment' => 'You have already passed this course assessment.']);
        }
        if ($assessment->questions->isEmpty()) {
            throw ValidationException::withMessages(['assessment' => 'This course assessment has no questions yet.']);
        }

        $attempt = $this->courseAssessments->createAttempt([
            'course_assessment_id' => $assessment->id,
            'user_id' => $trainee->id,
            'attempt_number' => $this->courseAssessments->nextAttemptNumber($assessment, $trainee),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        activity('lms')->causedBy($trainee)->performedOn($attempt)->event('course-assessment-attempt.started')
            ->withProperties(['course_assessment_id' => $assessment->id, 'attempt_number' => $attempt->attempt_number])->log('Course assessment attempt started');

        return $attempt;
    }

    public function submit(CourseAssessmentAttempt $attempt, array $answers, Enrollment $enrollment, User $trainee): CourseAssessmentAttempt
    {
        if ($attempt->user_id !== $trainee->id || $attempt->status !== 'in_progress') {
            throw new AuthorizationException('This course assessment attempt cannot be submitted.');
        }

        return DB::transaction(function () use ($attempt, $answers, $enrollment, $trainee): CourseAssessmentAttempt {
            $attempt = $this->courseAssessments->findAttemptForTaking($attempt);
            $earned = 0.0;
            $total = (float) $attempt->courseAssessment->questions->sum('marks');

            foreach ($attempt->courseAssessment->questions as $question) {
                $selected = array_values(array_unique(array_map('intval', Arr::wrap($answers[$question->id] ?? []))));
                sort($selected);
                $validOptionIds = $question->options->pluck('id')->map(fn ($id): int => (int) $id)->all();
                $selected = array_values(array_intersect($selected, $validOptionIds));
                $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
                $isCorrect = $selected === $correct;
                $marks = $isCorrect ? (float) $question->marks : 0.0;
                $earned += $marks;
                $this->courseAssessments->createAnswer([
                    'course_assessment_attempt_id' => $attempt->id,
                    'course_assessment_question_id' => $question->id,
                    'selected_option_ids' => $selected,
                    'earned_marks' => $marks,
                    'is_correct' => $isCorrect,
                ]);
            }

            $score = $total > 0 ? round($earned / $total * 100, 2) : 0;
            $passed = $score >= (float) $attempt->courseAssessment->passing_percentage;
            $attempt = $this->courseAssessments->updateAttempt($attempt, [
                'status' => 'graded', 'submitted_at' => now(), 'earned_marks' => $earned,
                'total_marks' => $total, 'score_percentage' => $score, 'passed' => $passed,
            ]);

            if ($passed) {
                $this->enrollments->completeMaterial($enrollment, $attempt->courseAssessment->material);
                $this->learning->recalculate($enrollment);
                $this->credits->recordAssessmentPass($attempt->courseAssessment, $trainee, $attempt->submitted_at);
            }

            activity('lms')->causedBy($trainee)->performedOn($attempt)->event('course-assessment-attempt.graded')
                ->withProperties(['course_assessment_id' => $attempt->course_assessment_id, 'score' => $score, 'passed' => $passed])
                ->log('Course assessment attempt graded');

            return $attempt;
        });
    }

    private function ensureEditable(CourseAssessment $assessment): void
    {
        if ($this->courseAssessments->hasAttempts($assessment)) {
            throw ValidationException::withMessages(['question' => 'Questions cannot change after a course assessment attempt has started.']);
        }
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
}
