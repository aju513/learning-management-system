<?php

namespace App\Console\Commands;

use App\Enums\SystemRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\NavigationService;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class SyncAdminPermissions extends Command
{
    protected $signature = 'admin:permissions-sync';

    protected $description = 'Exact-sync configured permissions and bootstrap the super admin role';

    public function handle(NavigationService $navigation): int
    {
        $permissions = PermissionCatalog::names();
        $systemRoles = SystemRole::values();

        if ($permissions->count() !== $permissions->unique()->count()) {
            $this->error('Duplicate permission names exist in config/permissions.php.');

            return self::FAILURE;
        }

        $unknownMenuPermissions = collect($navigation->permissionReferences())->diff($permissions);
        if ($unknownMenuPermissions->isNotEmpty()) {
            $this->error('Menu permissions missing from config/permissions.php: '.$unknownMenuPermissions->join(', '));

            return self::FAILURE;
        }

        $unsupportedAssignedRoles = Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $systemRoles)
            ->whereHas('users')
            ->pluck('name');
        if ($unsupportedAssignedRoles->isNotEmpty()) {
            $this->error('Users are assigned unsupported roles: '.$unsupportedAssignedRoles->join(', ').'. Resolve them before synchronizing.');

            return self::FAILURE;
        }

        if (User::query()->withCount('roles')->get()->contains(fn (User $user): bool => $user->roles_count > 1)) {
            $this->error('One or more users have multiple roles. Every account must have exactly one system role.');

            return self::FAILURE;
        }

        if (User::query()->whereKeyNot(1)->doesntHave('roles')->exists()) {
            $this->error('One or more users have no system role. Assign exactly one role before synchronizing.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($permissions): void {
                $existing = Permission::query()->where('guard_name', 'web')->pluck('name');

                foreach ($permissions as $permission) {
                    Permission::findOrCreate($permission, 'web');
                    $definition = PermissionCatalog::definitions()->get($permission);
                    Permission::query()
                        ->where('name', $permission)
                        ->where('guard_name', 'web')
                        ->update([
                            'view_title' => $definition['view_title'],
                            'description' => $definition['description'],
                            'updated_at' => now(),
                        ]);
                }

                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereNotIn('name', $permissions)
                    ->get()
                    ->each->delete();

                app(PermissionRegistrar::class)->forgetCachedPermissions();

                $role = Role::findOrCreate('super-admin', 'web');
                $role->syncPermissions($permissions
                    ->reject(fn (string $permission): bool => str_starts_with($permission, 'portals.')
                        && $permission !== SystemRole::SuperAdmin->portalPermission())
                    ->all());

                foreach (config('lms.roles', []) as $roleName => $rolePermissions) {
                    Role::findOrCreate($roleName, 'web')->syncPermissions($rolePermissions);
                }

                Role::query()
                    ->where('guard_name', 'web')
                    ->whereNotIn('name', SystemRole::values())
                    ->whereDoesntHave('users')
                    ->get()
                    ->each->delete();

                $user = User::query()->find(1);
                if (User::query()->doesntExist()) {
                    $user = User::query()->create([
                        'name' => 'Administrator',
                        'email' => 'admin@admin.com',
                        'password' => Hash::make('admin'),
                        'status' => UserStatus::Active,
                    ]);
                }

                $user?->syncRoles([$role]);

                activity('system')
                    ->event('permissions.synced')
                    ->withProperties([
                        'configured' => $permissions->count(),
                        'added' => $permissions->diff($existing)->values()->all(),
                        'removed' => $existing->diff($permissions)->values()->all(),
                        'bootstrap_user_id' => $user?->id,
                    ])
                    ->log('Admin permissions synchronized');
            });
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Permissions synchronized and super-admin updated.');
        if (! User::query()->find(1)) {
            $this->components->warn('User ID 1 does not exist; no user received the super-admin role.');
        }

        return self::SUCCESS;
    }
}
