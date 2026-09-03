<?php

namespace App\Repositories\Eloquent;

use App\Models\Assessment;
use App\Models\AssessmentApplication;
use App\Models\User;
use App\Repositories\Contracts\AssessmentApplicationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssessmentApplicationRepository implements AssessmentApplicationRepositoryInterface
{
    public function findForAssessmentAndTrainee(Assessment $assessment, User $trainee): ?AssessmentApplication
    {
        return AssessmentApplication::query()
            ->whereBelongsTo($assessment)
            ->where('user_id', $trainee->id)
            ->first();
    }

    public function createOrReset(Assessment $assessment, User $trainee): AssessmentApplication
    {
        return AssessmentApplication::query()->updateOrCreate(
            ['assessment_id' => $assessment->id, 'user_id' => $trainee->id],
            [
                'status' => 'pending',
                'requested_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_note' => null,
            ],
        );
    }

    public function update(AssessmentApplication $application, array $attributes): AssessmentApplication
    {
        $application->update($attributes);

        return $application->refresh();
    }

    public function lockForReview(AssessmentApplication $application): AssessmentApplication
    {
        return AssessmentApplication::query()
            ->whereKey($application->id)
            ->lockForUpdate()
            ->firstOrFail()
            ->load(['assessment.creator', 'trainee']);
    }

    public function paginateForReview(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return AssessmentApplication::query()
            ->with(['assessment.creator', 'trainee', 'reviewer'])
            ->when(! $actor->can('test-applications.review-all'), fn ($query) => $query->whereHas(
                'assessment',
                fn ($assessment) => $assessment->where('created_by', $actor->id),
            ))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereHas(
                'trainee',
                fn ($trainee) => $trainee->where(fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")),
            ))
            ->when($filters['assessment_id'] ?? null, fn ($query, int|string $assessment) => $query->where('assessment_id', $assessment))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('requested_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function assessmentsForReview(User $actor): Collection
    {
        return Assessment::query()
            ->when(! $actor->can('test-applications.review-all'), fn ($query) => $query->where('created_by', $actor->id))
            ->whereHas('applications')
            ->withCount([
                'applications as applied_count',
                'applications as approved_count' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->orderBy('title')
            ->get();
    }
}
