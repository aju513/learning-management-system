<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class LmsRoleAccess
{
    public static function assignableRoleNames(User $actor): Collection
    {
        if ($actor->hasRole('super-admin')) {
            return collect(['super-admin', ...array_keys(config('lms.roles', []))]);
        }

        return collect(config('lms.role_management_permissions', []))
            ->filter(fn (string $permission) => $actor->can($permission))
            ->keys()->values();
    }

    public static function canManage(User $actor, User $target): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }

        $targetRoles = $target->getRoleNames();
        if ($targetRoles->contains('super-admin')) {
            return false;
        }

        return $targetRoles->isEmpty() || $targetRoles->every(fn (string $role) => self::assignableRoleNames($actor)->contains($role));
    }
}
