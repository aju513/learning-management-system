<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityRepository implements ActivityRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Activity::query()
            ->with('causer')
            ->when($filters['event'] ?? null, fn ($query, string $event) => $query->where('event', 'like', "%{$event}%"))
            ->when($filters['actor'] ?? null, fn ($query, string $actor) => $query->whereHasMorph('causer', '*', function ($query) use ($actor): void {
                $query->where('name', 'like', "%{$actor}%")->orWhere('email', 'like', "%{$actor}%");
            }))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
