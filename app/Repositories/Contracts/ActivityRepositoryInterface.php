<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;
}
