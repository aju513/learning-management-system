<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use RuntimeException;

class PortalRoute
{
    public static function name(string $suffix): string
    {
        $current = Route::currentRouteName();
        if (! $current) {
            throw new RuntimeException('A portal route name is required.');
        }

        return str($current)->before('.')->append('.'.$suffix)->toString();
    }
}
