<?php

namespace App\Services\Training;

use Illuminate\Support\Collection;

interface TrainingCatalogProviderInterface
{
    /** @return Collection<int, array{key: string, name: string}> */
    public function all(): Collection;

    public function exists(string $key): bool;

    public function name(string $key): ?string;
}
