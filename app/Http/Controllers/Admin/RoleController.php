<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\DeleteRoleRequest;
use App\Http\Requests\Role\IndexRoleRequest;
use App\Http\Requests\Role\ShowRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\RoleService;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleRepositoryInterface $roles, private readonly RoleService $service) {}

    public function index(IndexRoleRequest $request): View
    {
        return view('pages.admin.roles.index', ['roles' => $this->roles->paginateForIndex($request->validated()), 'title' => 'Roles']);
    }

    public function create(): View
    {
        return view('pages.admin.roles.create', ['role' => new Role, 'permissionGroups' => PermissionCatalog::groups(), 'title' => 'Create Role']);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('pages.admin.roles.edit', ['role' => $this->roles->findForEdit($role), 'permissionGroups' => PermissionCatalog::groups(), 'title' => 'Edit Role']);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->update($role, $request->validated(), $request->user());

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function show(ShowRoleRequest $request, Role $role): View
    {
        return view('pages.admin.roles.show', ['role' => $this->roles->findForShow($role), 'title' => 'Role Details']);
    }

    public function destroy(DeleteRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->delete($role, $request->user());

        return back()->with('success', 'Role deleted.');
    }
}
