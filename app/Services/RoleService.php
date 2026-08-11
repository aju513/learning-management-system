<?php

namespace App\Services;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(private readonly RoleRepositoryInterface $roles) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, Authenticatable $actor): Role
    {
        return DB::transaction(function () use ($data, $actor): Role {
            $permissions = Arr::pull($data, 'permissions', []);
            $role = $this->roles->create([...$data, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
            activity('admin')->causedBy($actor)->performedOn($role)->event('role.created')
                ->withProperties(['permissions' => $permissions])->log('Role created');

            return $role;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Role $role, array $data, Authenticatable $actor): Role
    {
        if ($role->name === 'super-admin') {
            throw ValidationException::withMessages(['role' => 'The super-admin role is managed by the permission sync command.']);
        }

        return DB::transaction(function () use ($role, $data, $actor): Role {
            $permissions = Arr::pull($data, 'permissions', []);
            $role = $this->roles->update($role, $data);
            $role->syncPermissions($permissions);
            activity('admin')->causedBy($actor)->performedOn($role)->event('role.updated')
                ->withProperties(['permissions' => $permissions])->log('Role updated');

            return $role;
        });
    }

    public function delete(Role $role, Authenticatable $actor): void
    {
        if ($role->name === 'super-admin') {
            throw ValidationException::withMessages(['role' => 'The super-admin role cannot be deleted.']);
        }
        if ($this->roles->hasUsers($role)) {
            throw ValidationException::withMessages(['role' => 'Remove all assigned users before deleting this role.']);
        }

        DB::transaction(function () use ($role, $actor): void {
            activity('admin')->causedBy($actor)->performedOn($role)->event('role.deleted')
                ->withProperties(['name' => $role->name])->log('Role deleted');
            $this->roles->delete($role);
        });
    }
}
