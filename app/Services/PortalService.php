<?php

namespace App\Services;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class PortalService
{
    public function roleFor(User $user): SystemRole
    {
        $roles = $user->getRoleNames();

        if ($roles->count() !== 1 || ! in_array($roles->first(), SystemRole::values(), true)) {
            throw new AuthorizationException('Your account must have exactly one supported system role.');
        }

        return SystemRole::from($roles->first());
    }

    public function dashboardRouteFor(User $user): string
    {
        return $this->roleFor($user)->dashboardRoute();
    }
}
