<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('permissions.view');

        return view('pages.admin.permissions.index', ['permissionGroups' => PermissionCatalog::groups(), 'title' => 'Permissions']);
    }
}
