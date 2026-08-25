<?php

namespace App\Services\Training;

use Illuminate\Support\Collection;

class ConfigTrainingCatalogProvider implements TrainingCatalogProviderInterface
{
    public function all(): Collection
    {
        return collect(config('training.catalog', []))
            ->map(fn (array $training): array => [
                'key' => (string) $training['key'],
                'name' => (string) $training['name'],
            ])
            ->values();
    }

    public function exists(string $key): bool
    {
        return $this->all()->contains('key', $key);
    }

    public function name(string $key): ?string
    {
        $training = $this->all()->firstWhere('key', $key);

        return $training['name'] ?? null;
    }
}
