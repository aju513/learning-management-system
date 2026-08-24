<?php

namespace App\Repositories\Eloquent;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentAttempt;
use App\Models\CourseAssessmentQuestion;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Repositories\Contracts\CourseAssessmentRepositoryInterface;

class CourseAssessmentRepository implements CourseAssessmentRepositoryInterface
{
    public function create(array $attributes): CourseAssessment
    {
        return CourseAssessment::query()->create($attributes);
    }

    public function update(CourseAssessment $assessment, array $attributes): CourseAssessment
    {
        $assessment->update($attributes);

        return $assessment->refresh();
    }

    public function delete(CourseAssessment $assessment): void
    {
        $assessment->delete();
    }

    public function findForMaterial(LearningMaterial $material): ?CourseAssessment
    {
        return $material->courseAssessment()->first();
    }

    public function findForManagement(CourseAssessment $assessment): CourseAssessment
    {
        return $assessment->load(['material.chapter.module.course', 'questions.options'])
            ->loadCount([
                'attempts',
                'attempts as started_attempts' => fn ($query) => $query->whereNotNull('started_at'),
            ]);
    }

    public function hasAttempts(CourseAssessment $assessment): bool
    {
        return $assessment->attempts()->exists();
    }

    public function createQuestion(array $attributes): CourseAssessmentQuestion
    {
        return CourseAssessmentQuestion::query()->create($attributes);
    }

    public function findQuestionForEdit(CourseAssessmentQuestion $question): CourseAssessmentQuestion
    {
        return $question->load(['options', 'courseAssessment.material.chapter.module.course']);
    }

    public function updateQuestion(CourseAssessmentQuestion $question, array $attributes): CourseAssessmentQuestion
    {
        $question->update($attributes);

        return $question->refresh();
    }

    public function replaceOptions(CourseAssessmentQuestion $question, array $options): void
    {
        $question->options()->delete();
        $question->options()->createMany($options);
    }

    public function deleteQuestion(CourseAssessmentQuestion $question): void
    {
        $question->delete();
    }

    public function nextQuestionPosition(CourseAssessment $assessment): int
    {
        return ((int) $assessment->questions()->max('position')) + 1;
    }

    public function questionIds(CourseAssessment $assessment): array
    {
        return $assessment->questions()->orderBy('position')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function reorderQuestions(CourseAssessment $assessment, array $questionIds): void
    {
        foreach (array_values($questionIds) as $position => $questionId) {
            $assessment->questions()->whereKey($questionId)->update(['position' => $position + 1]);
        }
    }

    public function createAttempt(array $attributes): CourseAssessmentAttempt
    {
        return CourseAssessmentAttempt::query()->create($attributes);
    }

    public function updateAttempt(CourseAssessmentAttempt $attempt, array $attributes): CourseAssessmentAttempt
    {
        $attempt->update($attributes);

        return $attempt->refresh();
    }

    public function findAttemptForTaking(CourseAssessmentAttempt $attempt): CourseAssessmentAttempt
    {
        return $attempt->load(['courseAssessment.material.chapter.module.course', 'courseAssessment.questions.options', 'answers.question.options', 'trainee']);
    }

    public function activeAttempt(CourseAssessment $assessment, User $trainee): ?CourseAssessmentAttempt
    {
        return $assessment->attempts()->where('user_id', $trainee->id)->where('status', 'in_progress')->latest('id')->first();
    }

    public function nextAttemptNumber(CourseAssessment $assessment, User $trainee): int
    {
        return ((int) $assessment->attempts()->where('user_id', $trainee->id)->max('attempt_number')) + 1;
    }

    public function hasPassed(CourseAssessment $assessment, User $trainee): bool
    {
        return $assessment->attempts()->where('user_id', $trainee->id)->where('passed', true)->exists();
    }

    public function createAnswer(array $attributes): CourseAssessmentAnswer
    {
        return CourseAssessmentAnswer::query()->create($attributes);
    }
}
