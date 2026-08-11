<?php

namespace App\Console\Commands;

use App\Services\NavigationService;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Throwable;

class RegenerateAdminMenu extends Command
{
    protected $signature = 'admin:menu-regenerate';

    protected $description = 'Validate and atomically rebuild the compiled admin menu';

    public function handle(NavigationService $navigation): int
    {
        $items = config('admin-menu', []);
        $flat = $this->flatten($items);
        $permissions = PermissionCatalog::names();
        $keys = collect($flat)->pluck('key');

        $errors = [];
        if ($keys->count() !== $keys->unique()->count()) {
            $errors[] = 'Menu keys must be unique.';
        }

        foreach ($flat as $item) {
            if (isset($item['route']) && ! Route::has($item['route'])) {
                $errors[] = "Unknown route [{$item['route']}] for menu [{$item['key']}].";
            }
            if (isset($item['permission']) && ! $permissions->contains($item['permission'])) {
                $errors[] = "Unknown permission [{$item['permission']}] for menu [{$item['key']}].";
            }
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $target = base_path('bootstrap/cache/admin-menu.php');
            File::ensureDirectoryExists(dirname($target));
            File::replace($target, "<?php\n\nreturn ".var_export($navigation->configuredManifest(), true).";\n");
            activity('system')->event('menu.regenerated')->log('Admin menu regenerated');
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Admin menu cache regenerated.');

        return self::SUCCESS;
    }

    /** @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $items): array
    {
        $flat = [];
        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);
            $flat[] = $item;
            $flat = [...$flat, ...$this->flatten($children)];
        }

        return $flat;
    }
}
