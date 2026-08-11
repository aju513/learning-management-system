<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginateForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findForShow(Role $role): Role;

    public function findForEdit(Role $role): Role;

    /** @return Collection<int, Role> */
    public function allForAssignment(): Collection;

    public function hasUsers(Role $role): bool;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Role;

    /** @param array<string, mixed> $attributes */
    public function update(Role $role, array $attributes): Role;

    public function delete(Role $role): void;
}
