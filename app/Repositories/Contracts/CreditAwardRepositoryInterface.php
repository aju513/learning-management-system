<?php

namespace App\Repositories\Contracts;

use App\Enums\CreditAwardStatus;
use App\Models\CreditAward;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CreditAwardRepositoryInterface
{
    public function create(array $attributes): CreditAward;

    public function findByKey(FiscalYear $fiscalYear, User $user, string $sourceKey): ?CreditAward;

    public function forUser(User $user, ?FiscalYear $fiscalYear = null, int $perPage = 15): LengthAwarePaginator;

    public function eligibleForUser(User $user, ?FiscalYear $fiscalYear = null): LengthAwarePaginator;

    public function claimedTotal(User $user, ?FiscalYear $fiscalYear = null): float;

    public function eligibleTotal(User $user, ?FiscalYear $fiscalYear = null): float;

    public function claim(CreditAward $award): CreditAward;

    public function hasStatus(CreditAward $award, CreditAwardStatus $status): bool;
}
