<?php

namespace App\Repositories\Eloquent;

use App\Models\FiscalYear;
use App\Repositories\Contracts\FiscalYearRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FiscalYearRepository implements FiscalYearRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return FiscalYear::query()
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('starts_on')->paginate($perPage)->withQueryString();
    }

    public function create(array $attributes): FiscalYear
    {
        return FiscalYear::query()->create($attributes);
    }

    public function update(FiscalYear $fiscalYear, array $attributes): FiscalYear
    {
        $fiscalYear->update($attributes);

        return $fiscalYear->refresh();
    }

    public function delete(FiscalYear $fiscalYear): void
    {
        $fiscalYear->delete();
    }

    public function findContaining(DateTimeInterface $date): ?FiscalYear
    {
        return FiscalYear::query()->where('status', 'active')
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->first();
    }

    public function active(): ?FiscalYear
    {
        return FiscalYear::query()->where('status', 'active')->orderByDesc('starts_on')->first();
    }

    public function hasOverlap(DateTimeInterface $startsOn, DateTimeInterface $endsOn, ?FiscalYear $ignore = null): bool
    {
        return FiscalYear::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->whereDate('starts_on', '<=', $endsOn)->whereDate('ends_on', '>=', $startsOn)->exists();
    }

    public function hasRecords(FiscalYear $fiscalYear): bool
    {
        return $fiscalYear->awards()->exists() || $fiscalYear->attendanceSnapshots()->exists();
    }
}
