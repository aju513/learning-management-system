<?php

namespace App\Http\Controllers\Shared;

use App\Enums\SystemRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeRoleAccountStatusRequest;
use App\Http\Requests\User\DeleteRoleAccountRequest;
use App\Http\Requests\User\IndexRoleAccountRequest;
use App\Http\Requests\User\ManageRoleAccountRequest;
use App\Http\Requests\User\StoreRoleAccountRequest;
use App\Http\Requests\User\UpdateRoleAccountRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleAccountController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserService $service,
    ) {}

    public function index(IndexRoleAccountRequest $request): View
    {
        $role = $this->role($request);

        return view('modules.shared.accounts.index', [
            'users' => $this->users->paginateForRole($role->value, $request->validated()),
            'role' => $role,
            'routeBase' => $this->routeBase($request),
            'title' => $role->label().' Accounts',
        ]);
    }

    public function create(ManageRoleAccountRequest $request): View
    {
        $role = $this->role($request);

        return view('modules.shared.accounts.form', [
            'user' => new User(['status' => UserStatus::Active]),
            'role' => $role,
            'routeBase' => $this->routeBase($request),
            'title' => 'Create '.$role->label(),
        ]);
    }

    public function store(StoreRoleAccountRequest $request): RedirectResponse
    {
        $this->service->create($request->validated() + ['roles' => [$this->role($request)->value]], $request->user());

        return redirect()->route($this->routeBase($request).'.index')->with('success', 'Account created.');
    }

    public function edit(ManageRoleAccountRequest $request, User $user): View
    {
        $role = $this->role($request);

        return view('modules.shared.accounts.form', [
            'user' => $this->users->findForEdit($user),
            'role' => $role,
            'routeBase' => $this->routeBase($request),
            'title' => 'Edit '.$role->label(),
        ]);
    }

    public function update(UpdateRoleAccountRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated() + ['roles' => [$this->role($request)->value]], $request->user());

        return redirect()->route($this->routeBase($request).'.index')->with('success', 'Account updated.');
    }

    public function status(ChangeRoleAccountStatusRequest $request, User $user): RedirectResponse
    {
        $this->service->changeStatus($user, UserStatus::from($request->validated('status')), $request->user());

        return back()->with('success', 'Account status updated.');
    }

    public function destroy(DeleteRoleAccountRequest $request, User $user): RedirectResponse
    {
        $this->service->delete($user, $request->user());

        return redirect()->route($this->routeBase($request).'.index')->with('success', 'Account deleted.');
    }

    private function role(\Illuminate\Http\Request $request): SystemRole
    {
        $resource = str($request->route()->getName())->after('.')->before('.')->toString();

        return match ($resource) {
            'admins' => SystemRole::Admin,
            'instructors' => SystemRole::Instructor,
            'trainees' => SystemRole::Trainee,
        };
    }

    private function routeBase(\Illuminate\Http\Request $request): string
    {
        return str($request->route()->getName())->beforeLast('.')->toString();
    }
}
