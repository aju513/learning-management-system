<?php

namespace App\Repositories\Eloquent;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AttemptAnswer;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssessmentRepository implements AssessmentRepositoryInterface
{
    public function paginate(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return Assessment::query()->with('creator')->withCount(['questions', 'attempts'])
            ->when(! $actor->can('assessments.view-all'), fn ($query) => $query->where('created_by', $actor->id))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findForManagement(Assessment $assessment): Assessment
    {
        return $assessment->load(['creator', 'questions.options', 'assignments.trainee'])
            ->loadCount('attempts');
    }

    public function findAssessment(int $id): Assessment
    {
        return Assessment::query()->findOrFail($id);
    }

    public function attachable(User $actor): Collection
    {
        return Assessment::query()->where('status', '!=', 'closed')
            ->when(! $actor->can('assessments.edit-any'), fn ($query) => $query->where('created_by', $actor->id))
            ->orderBy('title')->get();
    }

    public function allForFilter(): Collection
    {
        return Assessment::query()->orderBy('title')->get(['id', 'title']);
    }

    public function create(array $attributes): Assessment
    {
        return Assessment::query()->create($attributes);
    }

    public function update(Assessment $assessment, array $attributes): Assessment
    {
        $assessment->update($attributes);

        return $assessment->refresh();
    }

    public function delete(Assessment $assessment): void
    {
        $assessment->delete();
    }

    public function hasAttempts(Assessment $assessment): bool
    {
        return $assessment->attempts()->exists();
    }

    public function findForAvailability(Assessment $assessment): Assessment
    {
        return $assessment;
    }

    public function createQuestion(array $attributes): AssessmentQuestion
    {
        return AssessmentQuestion::query()->create($attributes);
    }

    public function findQuestionForEdit(AssessmentQuestion $question): AssessmentQuestion
    {
        return $question->load(['options', 'assessment']);
    }

    public function updateQuestion(AssessmentQuestion $question, array $attributes): AssessmentQuestion
    {
        $question->update($attributes);

        return $question->refresh();
    }

    public function replaceOptions(AssessmentQuestion $question, array $options): void
    {
        $question->options()->delete();
        $question->options()->createMany($options);
    }

    public function deleteQuestion(AssessmentQuestion $question): void
    {
        $question->delete();
    }

    public function nextQuestionPosition(Assessment $assessment): int
    {
        return ((int) $assessment->questions()->max('position')) + 1;
    }

    public function questionIds(Assessment $assessment): array
    {
        return $assessment->questions()->orderBy('position')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function reorderQuestions(Assessment $assessment, array $questionIds): void
    {
        foreach (array_values($questionIds) as $position => $questionId) {
            $assessment->questions()->whereKey($questionId)->update(['position' => $position + 1]);
        }
    }

    public function assign(Assessment $assessment, User $trainee, User $actor, ?string $dueAt): AssessmentAssignment
    {
        return AssessmentAssignment::query()->updateOrCreate(
            ['assessment_id' => $assessment->id, 'user_id' => $trainee->id],
            ['assigned_by' => $actor->id, 'assigned_at' => now(), 'due_at' => $dueAt],
        );
    }

    public function unassign(AssessmentAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function availableFor(User $trainee): Collection
    {
        return Assessment::query()->where('status', 'published')
            ->whereHas('assignments', fn ($assignment) => $assignment->where('user_id', $trainee->id))
            ->withCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)])
            ->orderBy('ends_at')->orderBy('title')->get();
    }

    public function userCanTake(Assessment $assessment, User $trainee): bool
    {
        return $assessment->assignments()->where('user_id', $trainee->id)->exists();
    }

    public function countAttempts(Assessment $assessment, User $trainee): int
    {
        return $assessment->attempts()->where('user_id', $trainee->id)->count();
    }

    public function activeAttempt(Assessment $assessment, User $trainee): ?AssessmentAttempt
    {
        return $assessment->attempts()->where('user_id', $trainee->id)->where('status', 'in_progress')->latest('id')->first();
    }

    public function hasPassed(Assessment $assessment, User $trainee): bool
    {
        return $assessment->attempts()->where('user_id', $trainee->id)->where('passed', true)->exists();
    }

    public function createAttempt(array $attributes): AssessmentAttempt
    {
        return AssessmentAttempt::query()->create($attributes);
    }

    public function findAttemptForTaking(AssessmentAttempt $attempt): AssessmentAttempt
    {
        return $attempt->load(['assessment.questions.options', 'answers.reviewer', 'trainee']);
    }

    public function createAnswer(array $attributes): AttemptAnswer
    {
        return AttemptAnswer::query()->create($attributes);
    }

    public function updateAnswer(AttemptAnswer $answer, array $attributes): AttemptAnswer
    {
        $answer->update($attributes);

        return $answer->refresh();
    }

    public function updateAttempt(AssessmentAttempt $attempt, array $attributes): AssessmentAttempt
    {
        $attempt->update($attributes);

        return $attempt->refresh();
    }

    public function paginateResults(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return AssessmentAttempt::query()->whereIn('status', ['graded', 'pending_review'])->with(['assessment', 'trainee'])
            ->when(! $actor->can('results.view-all') && $actor->can('results.view-owned'), fn ($query) => $query->whereHas('assessment', fn ($assessment) => $assessment->where('created_by', $actor->id)))
            ->when(! $actor->can('results.view-all') && ! $actor->can('results.view-owned'), fn ($query) => $query->where('status', 'graded')->where('user_id', $actor->id)->whereHas('assessment', fn ($assessment) => $assessment->where('show_results', true)))
            ->when($filters['assessment_id'] ?? null, fn ($query, int|string $assessment) => $query->where('assessment_id', $assessment))
            ->when(isset($filters['passed']) && $filters['passed'] !== '', fn ($query) => $query->where('passed', (bool) $filters['passed']))
            ->latest('submitted_at')->paginate($perPage)->withQueryString();
    }
}
