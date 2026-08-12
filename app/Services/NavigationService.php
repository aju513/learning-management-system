<?php

namespace App\Services;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Support\Arr;

class NavigationService
{
    /** @return array<int, array<string, mixed>> */
    public function forUser(User $user): array
    {
        $role = app(PortalService::class)->roleFor($user);
        $items = $this->manifest($role);

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
    public function manifest(SystemRole $role): array
    {
        $cache = base_path('bootstrap/cache/admin-menu.php');
        $manifests = is_file($cache) ? require $cache : config('admin-menu', []);
        $items = $manifests[$role->value] ?? [];

        return $this->sort($items);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function configuredManifests(): array
    {
        return collect(config('admin-menu', []))
            ->map(fn (array $items): array => $this->sort($items))
            ->all();
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
        return collect(config('admin-menu', []))
            ->flatMap(fn (array $items): array => $this->flatten($items))
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
