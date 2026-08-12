<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('permissions.view');

        return view('pages.admin.permissions.index', [
            'permissionGroups' => PermissionCatalog::groups(),
            'roleMatrices' => collect(SystemRole::cases())->mapWithKeys(fn (SystemRole $role): array => [
                $role->label() => $role === SystemRole::SuperAdmin
                    ? PermissionCatalog::names()->all()
                    : config("lms.roles.{$role->value}", []),
            ]),
            'title' => 'Fixed Access Matrix',
        ]);
    }
}
