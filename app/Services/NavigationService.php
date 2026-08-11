<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class NavigationService
{
    /** @return array<int, array<string, mixed>> */
    public function forUser(User $user): array
    {
        $items = $this->manifest();

        return array_values(array_filter(array_map(function (array $item) use ($user): ?array {
            if (isset($item['children'])) {
                $item['children'] = array_values(array_filter(
                    $item['children'],
                    fn (array $child): bool => $user->can($child['permission'])
                ));

                return $item['children'] === [] ? null : $item;
            }

            return $user->can($item['permission']) ? $item : null;
        }, $items)));
    }

    /** @return array<int, array<string, mixed>> */
    public function manifest(): array
    {
        $cache = base_path('bootstrap/cache/admin-menu.php');
        $items = is_file($cache) ? require $cache : config('admin-menu', []);

        return $this->sort($items);
    }

    /** @return array<int, array<string, mixed>> */
    public function configuredManifest(): array
    {
        return $this->sort(config('admin-menu', []));
    }

    /** @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sort(array $items): array
    {
        return collect($items)
            ->sortBy('order')
            ->map(function (array $item): array {
                if (isset($item['children'])) {
                    $item['children'] = collect($item['children'])->sortBy('order')->values()->all();
                }

                return $item;
            })
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function permissionReferences(): array
    {
        return collect($this->flatten(config('admin-menu', [])))
            ->pluck('permission')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $items): array
    {
        return collect($items)->flatMap(function (array $item): array {
            $children = Arr::pull($item, 'children', []);

            return [$item, ...$this->flatten($children)];
        })->all();
    }
}
