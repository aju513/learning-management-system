<?php

namespace App\Http\Requests\User\Concerns;

use App\Enums\SystemRole;
use App\Models\User;

trait AuthorizesManagedRole
{
    protected function managedRole(): ?SystemRole
    {
        $resource = str((string) $this->route()->getName())->after('.')->before('.')->toString();
        $role = match ($resource) {
            'admins' => SystemRole::Admin,
            'instructors' => SystemRole::Instructor,
            'trainees' => SystemRole::Trainee,
            default => null,
        };

        return $role;
    }

    protected function canManageConfiguredRole(): bool
    {
        $role = $this->managedRole();
        $permission = $role ? config("lms.role_management_permissions.{$role->value}") : null;

        return is_string($permission) && ($this->user()?->can($permission) ?? false);
    }

    protected function targetMatchesManagedRole(): bool
    {
        $target = $this->route('user');
        $role = $this->managedRole();

        return ! $target instanceof User || ($role && $target->roles()->count() === 1 && $target->hasRole($role->value));
    }
}
