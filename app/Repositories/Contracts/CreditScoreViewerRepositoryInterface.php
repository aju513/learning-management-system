<?php

namespace App\Repositories\Contracts;

use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CreditScoreViewerRepositoryInterface
{
    public function fiscalYears(): Collection;

    public function findFiscalYear(?int $fiscalYearId = null): ?FiscalYear;

    public function paginateTraineeSummaries(FiscalYear $fiscalYear, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findTrainee(int $traineeId): ?User;

    public function details(FiscalYear $fiscalYear, User $trainee): array;
}
