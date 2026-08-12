<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function findActiveByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', Str::lower($email))
            ->where('status', UserStatus::Active->value)
            ->first();
    }

    public function paginateForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForRole(string $role, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->role($role)
            ->with('roles')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForShow(User $user): User
    {
        return $user->load('roles');
    }

    public function findForEdit(User $user): User
    {
        return $user->load('roles');
    }

    /** @param array<int, int|string> $ids
     * @return Collection<int, User>
     */
    public function findByIds(array $ids): Collection
    {
        return User::query()->whereIn('id', $ids)->get();
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function countSuperAdminsExcluding(User $user): int
    {
        return User::role('super-admin')->whereKeyNot($user->getKey())->count();
    }
}
