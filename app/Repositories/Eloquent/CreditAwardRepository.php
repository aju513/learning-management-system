<?php

namespace App\Repositories\Eloquent;

use App\Enums\CreditAwardStatus;
use App\Models\Course;
use App\Models\CreditAward;
use App\Models\FiscalYear;
use App\Models\User;
use App\Repositories\Contracts\CreditAwardRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CreditAwardRepository implements CreditAwardRepositoryInterface
{
    public function create(array $attributes): CreditAward
    {
        return CreditAward::query()->create($attributes);
    }

    public function findByKey(FiscalYear $fiscalYear, User $user, string $sourceKey): ?CreditAward
    {
        return CreditAward::query()->where('fiscal_year_id', $fiscalYear->id)->where('user_id', $user->id)->where('source_key', $sourceKey)->first();
    }

    public function findCourseAward(Course $course, User $user): ?CreditAward
    {
        return CreditAward::query()->with('fiscalYear')
            ->where('user_id', $user->id)
            ->where('source_key', 'course:'.$course->id)
            ->latest('eligible_at')
            ->first();
    }

    public function forUser(User $user, ?FiscalYear $fiscalYear = null, int $perPage = 15): LengthAwarePaginator
    {
        return CreditAward::query()->with(['fiscalYear', 'course', 'assessment'])
            ->where('user_id', $user->id)
            ->when($fiscalYear, fn ($query) => $query->where('fiscal_year_id', $fiscalYear->id))
            ->latest('eligible_at')->paginate($perPage)->withQueryString();
    }

    public function eligibleForUser(User $user, ?FiscalYear $fiscalYear = null, int $perPage = 10): LengthAwarePaginator
    {
        return CreditAward::query()->with(['course', 'assessment'])
            ->where('user_id', $user->id)->where('status', CreditAwardStatus::Eligible)
            ->when($fiscalYear, fn ($query) => $query->where('fiscal_year_id', $fiscalYear->id))
            ->latest('eligible_at')->paginate($perPage, ['*'], 'eligible_page')->withQueryString();
    }

    public function claimedTotal(User $user, ?FiscalYear $fiscalYear = null): float
    {
        return (float) CreditAward::query()->where('user_id', $user->id)->where('status', CreditAwardStatus::Claimed)
            ->when($fiscalYear, fn ($query) => $query->where('fiscal_year_id', $fiscalYear->id))->sum('points');
    }

    public function eligibleTotal(User $user, ?FiscalYear $fiscalYear = null): float
    {
        return (float) CreditAward::query()->where('user_id', $user->id)->where('status', CreditAwardStatus::Eligible)
            ->when($fiscalYear, fn ($query) => $query->where('fiscal_year_id', $fiscalYear->id))->sum('points');
    }

    public function claim(CreditAward $award): CreditAward
    {
        $locked = CreditAward::query()->lockForUpdate()->findOrFail($award->id);
        if ($locked->status !== CreditAwardStatus::Eligible) {
            throw ValidationException::withMessages(['award' => 'This credit has already been claimed.']);
        }
        $locked->update(['status' => CreditAwardStatus::Claimed, 'claimed_at' => now()]);

        return $locked->refresh();
    }

    public function hasStatus(CreditAward $award, CreditAwardStatus $status): bool
    {
        return CreditAward::query()->whereKey($award->id)->where('status', $status)->exists();
    }
}
