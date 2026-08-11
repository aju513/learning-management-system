<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class PermissionCatalog
{
    /** @return array<string, array<string, array{name: string, view_title: string, description: string}>> */
    public static function groups(): array
    {
        $groups = [];

        foreach (config('permissions', []) as $group => $definitions) {
            $groups[$group] = [];

            foreach ($definitions as $key => $definition) {
                $name = is_int($key) ? $definition : $key;
                $metadata = is_int($key) || ! is_array($definition) ? [] : $definition;

                if (! is_string($name) || $name === '') {
                    continue;
                }

                $groups[$group][$name] = [
                    'name' => $name,
                    'view_title' => $metadata['view_title'] ?? $metadata['label'] ?? Str::headline(Str::afterLast($name, '.')),
                    'description' => $metadata['description'] ?? '',
                ];
            }
        }

        return $groups;
    }

    /** @return Collection<int, string> */
    public static function names(): Collection
    {
        return collect(self::groups())
            ->flatMap(fn (array $permissions): array => array_keys($permissions))
            ->values();
    }

    /** @return Collection<string, array{name: string, view_title: string, description: string}> */
    public static function definitions(): Collection
    {
        return collect(self::groups())
            ->flatMap(fn (array $permissions): array => array_values($permissions))
            ->keyBy('name');
    }
}
