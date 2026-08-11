<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->withCount('users')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForShow(Role $role): Role
    {
        return $role->load(['permissions', 'users' => fn ($query) => $query->orderBy('name')])->loadCount('users');
    }

    public function findForEdit(Role $role): Role
    {
        return $role->load('permissions');
    }

    /** @return Collection<int, Role> */
    public function allForAssignment(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    public function hasUsers(Role $role): bool
    {
        return $role->users()->exists();
    }

    public function create(array $attributes): Role
    {
        return Role::query()->create($attributes);
    }

    public function update(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        return $role->refresh();
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
