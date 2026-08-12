<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function findActiveByEmail(string $email): ?User;

    /** @param array<string, mixed> $filters */
    public function paginateForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function paginateForRole(string $role, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findForShow(User $user): User;

    public function findForEdit(User $user): User;

    /** @param array<int, int|string> $ids
     * @return Collection<int, User>
     */
    public function findByIds(array $ids): Collection;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User;

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User;

    public function delete(User $user): void;

    public function countSuperAdminsExcluding(User $user): int;
}
