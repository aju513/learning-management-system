<?php

namespace App\Repositories\Eloquent;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentCategory;
use App\Models\AssessmentQuestion;
use App\Models\AttemptAnswer;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssessmentRepository implements AssessmentRepositoryInterface
{
    public function paginateCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return AssessmentCategory::query()->withCount('assessments')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function activeCategories(): Collection
    {
        return AssessmentCategory::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function categorySlugExists(string $slug, ?AssessmentCategory $ignore = null): bool
    {
        return AssessmentCategory::query()->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists();
    }

    public function createCategory(array $attributes): AssessmentCategory
    {
        return AssessmentCategory::query()->create($attributes);
    }

    public function updateCategory(AssessmentCategory $category, array $attributes): AssessmentCategory
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function categoryHasAssessments(AssessmentCategory $category): bool
    {
        return $category->assessments()->exists();
    }

    public function deleteCategory(AssessmentCategory $category): void
    {
        $category->delete();
    }

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

    public function availableFor(User $trainee, array $eligibleTrainingKeys = []): Collection
    {
        return $this->traineeAssessmentsQuery($trainee, $eligibleTrainingKeys)->get();
    }

    public function paginatePublishedCatalog(array $filters, User $trainee, array $eligibleTrainingKeys = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->publishedCatalogQuery($trainee, $eligibleTrainingKeys)
            ->with([
                'category',
                'creator',
                'applications' => fn ($query) => $query->where('user_id', $trainee->id),
                'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
                'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('id'),
            ])
            ->withCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($filters['category_id'] ?? null, fn ($query, int|string $category) => $query->where('category_id', $category))
            ->orderBy('title')->paginate($perPage)->withQueryString();
    }

    public function availableCategoriesForCatalog(User $trainee, array $eligibleTrainingKeys = [], int $limit = 8): Collection
    {
        return AssessmentCategory::query()->where('is_active', true)
            ->whereHas('assessments', fn ($query) => $this->applyCatalogAvailability($query, $eligibleTrainingKeys))
            ->withCount(['assessments' => fn ($query) => $this->applyCatalogAvailability($query, $eligibleTrainingKeys)])
            ->orderByDesc('assessments_count')->orderBy('name')->limit($limit)->get();
    }

    public function findPublishedCatalogAssessment(Assessment $assessment, User $trainee, array $eligibleTrainingKeys = []): Assessment
    {
        abort_unless($this->publishedCatalogQuery($trainee, $eligibleTrainingKeys)->whereKey($assessment->id)->exists(), 404);

        return $assessment->load([
            'category',
            'creator',
            'applications' => fn ($query) => $query->where('user_id', $trainee->id),
            'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
            'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('id'),
        ])->loadCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)]);
    }

    public function appliedFor(User $trainee, array $eligibleTrainingKeys = []): Collection
    {
        return $this->traineeAssessmentsQuery($trainee, $eligibleTrainingKeys)
            ->with([
                'category',
                'applications' => fn ($query) => $query->where('user_id', $trainee->id),
                'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
                'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('id'),
            ])
            ->withCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)])
            ->whereDoesntHave('attempts', fn ($query) => $query->where('user_id', $trainee->id))
            ->get();
    }

    public function enrolledFor(User $trainee, array $eligibleTrainingKeys = [], array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Assessment::query()
            ->where(function ($related) use ($trainee): void {
                $related->whereHas('applications', fn ($query) => $query->where('user_id', $trainee->id))
                    ->orWhereHas('assignments', fn ($query) => $query->where('user_id', $trainee->id))
                    ->orWhereHas('attempts', fn ($query) => $query->where('user_id', $trainee->id));
            })
            ->with([
                'category',
                'applications' => fn ($query) => $query->where('user_id', $trainee->id),
                'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
                'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('id'),
            ])
            ->withCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"));

        $query->when($filters['status'] ?? 'all', function ($query, string $status) use ($trainee): void {
            match ($status) {
                'completed', 'passed' => $query->whereHas('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->where('status', 'graded')->where('passed', true)),
                'failed' => $query->whereHas('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->where('status', 'graded')->where('passed', false))
                    ->whereDoesntHave('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->where('passed', true))
                    ->whereDoesntHave('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->whereIn('status', ['in_progress', 'pending_review'])),
                'pending' => $query->where(function ($pending) use ($trainee): void {
                    $pending->whereHas('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->whereIn('status', ['in_progress', 'pending_review']))
                        ->orWhereHas('applications', fn ($application) => $application->where('user_id', $trainee->id)->where('status', 'pending'));
                }),
                'application_pending' => $query->whereHas('applications', fn ($application) => $application->where('user_id', $trainee->id)->where('status', 'pending')),
                'rejected' => $query->whereHas('applications', fn ($application) => $application->where('user_id', $trainee->id)->whereIn('status', ['rejected', 'cancelled']))
                    ->whereDoesntHave('assignments', fn ($assignment) => $assignment->where('user_id', $trainee->id)),
                'ready', 'not_started' => $query->whereHas('assignments', fn ($assignment) => $assignment->where('user_id', $trainee->id))
                    ->whereDoesntHave('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)),
                'in_progress' => $query->whereHas('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->where('status', 'in_progress')),
                'pending_review' => $query->whereHas('attempts', fn ($attempt) => $attempt->where('user_id', $trainee->id)->where('status', 'pending_review')),
                'unavailable' => $query->where(function ($unavailable) use ($eligibleTrainingKeys): void {
                    $unavailable->where('status', '!=', 'published')
                        ->orWhere(fn ($dates) => $dates->whereNotNull('starts_at')->where('starts_at', '>', now()))
                        ->orWhere(fn ($dates) => $dates->whereNotNull('ends_at')->where('ends_at', '<', now()))
                        ->orWhere(fn ($training) => $training->where('availability_scope', 'training')->whereNotIn('required_training_key', $eligibleTrainingKeys));
                }),
                default => null,
            };
        });

        return $query
            ->when(($filters['sort'] ?? 'recent') === 'title', fn ($query) => $query->orderBy('title'), fn ($query) => $query->orderByDesc(
                AssessmentAssignment::select('assigned_at')
                    ->whereColumn('assessment_assignments.assessment_id', 'assessments.id')
                    ->where('user_id', $trainee->id)
                    ->limit(1)
            ))
            ->orderBy('title')->paginate($perPage)->withQueryString();
    }

    public function findForTrainee(Assessment $assessment, User $trainee): Assessment
    {
        abort_unless(Assessment::query()->whereKey($assessment->id)->where(function ($related) use ($trainee): void {
            $related->whereHas('applications', fn ($query) => $query->where('user_id', $trainee->id))
                ->orWhereHas('assignments', fn ($query) => $query->where('user_id', $trainee->id))
                ->orWhereHas('attempts', fn ($query) => $query->where('user_id', $trainee->id));
        })->exists(), 404);

        return $assessment->load([
            'category',
            'applications' => fn ($query) => $query->where('user_id', $trainee->id),
            'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
            'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('id'),
            'attempts.answers',
        ])->loadCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)]);
    }

    public function assignmentFor(Assessment $assessment, User $trainee): ?AssessmentAssignment
    {
        return AssessmentAssignment::query()->where('assessment_id', $assessment->id)->where('user_id', $trainee->id)->first();
    }

    public function creditAssessmentsForTrainee(User $trainee, array $eligibleTrainingKeys = [], ?int $fiscalYearId = null, int $limit = 12): Collection
    {
        return Assessment::query()->where('status', 'published')->where('credit_points', '>', 0)
            ->where(function ($query) use ($eligibleTrainingKeys): void {
                $query->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
            ->whereHas('assignments', fn ($assignment) => $assignment->where('user_id', $trainee->id))
            ->with([
                'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
                'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('submitted_at'),
                'creditAwards' => fn ($query) => $query->where('user_id', $trainee->id)->when($fiscalYearId, fn ($awardQuery) => $awardQuery->where('fiscal_year_id', $fiscalYearId)),
            ])
            ->withCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)])
            ->orderBy('ends_at')->orderBy('title')->limit($limit)->get();
    }

    private function traineeAssessmentsQuery(User $trainee, array $eligibleTrainingKeys = []): Builder
    {
        return Assessment::query()->where('status', 'published')
            ->where(function ($query) use ($eligibleTrainingKeys): void {
                $query->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
            ->whereHas('assignments', fn ($assignment) => $assignment->where('user_id', $trainee->id))
            ->with([
                'assignments' => fn ($query) => $query->where('user_id', $trainee->id),
                'attempts' => fn ($query) => $query->where('user_id', $trainee->id)->latest('id'),
            ])
            ->withCount(['questions', 'attempts' => fn ($query) => $query->where('user_id', $trainee->id)]);
    }

    private function publishedCatalogQuery(User $trainee, array $eligibleTrainingKeys = []): Builder
    {
        return Assessment::query()->where('status', 'published')
            ->where(function ($query) use ($eligibleTrainingKeys): void {
                $query->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    private function applyCatalogAvailability(Builder $query, array $eligibleTrainingKeys = []): void
    {
        $query->where('status', 'published')
            ->where(function ($availability) use ($eligibleTrainingKeys): void {
                $availability->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
            ->where(fn ($date) => $date->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($date) => $date->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
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

    public function findAttemptForSubmission(AssessmentAttempt $attempt): AssessmentAttempt
    {
        return AssessmentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail()
            ->load(['assessment.questions.options', 'answers.reviewer', 'trainee']);
    }

    public function createAnswer(array $attributes): AttemptAnswer
    {
        return AttemptAnswer::query()->create($attributes);
    }

    public function upsertAnswer(AssessmentAttempt $attempt, AssessmentQuestion $question, array $attributes): AttemptAnswer
    {
        return AttemptAnswer::query()->updateOrCreate(
            ['assessment_attempt_id' => $attempt->id, 'assessment_question_id' => $question->id],
            $attributes,
        );
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
