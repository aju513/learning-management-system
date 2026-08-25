<?php

namespace App\Repositories\Contracts;

use App\Models\FiscalYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FiscalYearRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): FiscalYear;

    public function update(FiscalYear $fiscalYear, array $attributes): FiscalYear;

    public function delete(FiscalYear $fiscalYear): void;

    public function findContaining(\DateTimeInterface $date): ?FiscalYear;

    public function active(): ?FiscalYear;

    public function hasOverlap(\DateTimeInterface $startsOn, \DateTimeInterface $endsOn, ?FiscalYear $ignore = null): bool;

    public function hasRecords(FiscalYear $fiscalYear): bool;
}
