<?php

namespace App\Services;

use App\Enums\SystemRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\LmsRoleAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, Authenticatable $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $canAssignRoles = $actor->can('users.assign-roles');
            $roles = Arr::pull($data, 'roles', []);
            $this->ensureAssignableRoles($roles, $actor);
            $data['password'] = Hash::make($data['password']);
            $user = $this->users->create($data);
            if ($canAssignRoles) {
                $user->syncRoles($roles);
            }

            activity('admin')->causedBy($actor)->performedOn($user)->event('user.created')
                ->withProperties(['roles' => $roles, 'email' => $user->email])->log('User created');

            return $user;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data, Authenticatable $actor): User
    {
        $this->ensureCanManageUser($actor, $user);

        return DB::transaction(function () use ($user, $data, $actor): User {
            $before = $user->only(['name', 'email', 'status']);
            $canAssignRoles = $actor->can('users.assign-roles');
            $roles = Arr::pull($data, 'roles', []);
            $this->ensureAssignableRoles($roles, $actor);
            if ($canAssignRoles && $user->hasRole('super-admin') && ! in_array('super-admin', $roles, true) && $this->users->countSuperAdminsExcluding($user) === 0) {
                throw ValidationException::withMessages(['roles' => 'The last super administrator cannot be demoted.']);
            }
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
            $user = $this->users->update($user, $data);
            if ($canAssignRoles) {
                $user->syncRoles($roles);
            }

            activity('admin')->causedBy($actor)->performedOn($user)->event('user.updated')
                ->withProperties(['old' => $before, 'attributes' => $user->only(['name', 'email', 'status']), 'roles' => $roles])
                ->log('User updated');

            return $user;
        });
    }

    public function changeStatus(User $user, UserStatus $status, Authenticatable $actor): User
    {
        $this->ensureCanManageUser($actor, $user);

        if ($status === UserStatus::Inactive && $user->hasRole('super-admin') && $this->users->countSuperAdminsExcluding($user) === 0) {
            throw ValidationException::withMessages(['status' => 'The last super administrator cannot be deactivated.']);
        }

        $old = $user->status->value;
        $user = $this->users->update($user, ['status' => $status]);
        activity('admin')->causedBy($actor)->performedOn($user)->event('user.status-changed')
            ->withProperties(['old' => $old, 'new' => $status->value])->log('User status changed');

        return $user;
    }

    /** @param array<int, int|string> $ids */
    public function bulkChangeStatus(array $ids, UserStatus $status, Authenticatable $actor): void
    {
        DB::transaction(function () use ($ids, $status, $actor): void {
            $users = $this->users->findByIds($ids);
            if ($users->count() !== count($ids)) {
                throw ValidationException::withMessages(['users' => 'One or more selected users could not be found.']);
            }

            foreach ($users as $user) {
                $this->changeStatus($user, $status, $actor);
            }
        });
    }

    public function delete(User $user, Authenticatable $actor): void
    {
        $this->ensureCanManageUser($actor, $user);

        if ($user->hasRole('super-admin') && $this->users->countSuperAdminsExcluding($user) === 0) {
            throw ValidationException::withMessages(['user' => 'The last super administrator cannot be deleted.']);
        }

        DB::transaction(function () use ($user, $actor): void {
            $snapshot = ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'roles' => $user->getRoleNames()->all()];
            activity('admin')->causedBy($actor)->performedOn($user)->event('user.deleted')
                ->withProperties($snapshot)->log('User deleted');
            $this->users->delete($user);
        });
    }

    /** @param array<int, int|string> $ids */
    public function bulkDelete(array $ids, Authenticatable $actor): void
    {
        if (in_array((string) $actor->getAuthIdentifier(), array_map('strval', $ids), true)) {
            throw ValidationException::withMessages(['users' => 'You cannot delete your own account in a bulk action.']);
        }

        DB::transaction(function () use ($ids, $actor): void {
            $users = $this->users->findByIds($ids);
            if ($users->count() !== count($ids)) {
                throw ValidationException::withMessages(['users' => 'One or more selected users could not be found.']);
            }

            foreach ($users as $user) {
                $this->delete($user, $actor);
            }
        });
    }

    private function ensureAssignableRoles(array $roles, Authenticatable $actor): void
    {
        if (count($roles) !== 1 || ! in_array($roles[0], SystemRole::values(), true)) {
            throw ValidationException::withMessages(['roles' => 'Every account must have exactly one supported system role.']);
        }

        if (! $actor instanceof User || ! LmsRoleAccess::assignableRoleNames($actor)->contains($roles[0])) {
            throw ValidationException::withMessages(['roles' => 'You cannot assign one or more selected roles.']);
        }
    }

    private function ensureCanManageUser(Authenticatable $actor, User $user): void
    {
        if (! $actor instanceof User || ! LmsRoleAccess::canManage($actor, $user)) {
            throw new AuthorizationException('You cannot manage this user account.');
        }
    }
}
