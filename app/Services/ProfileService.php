<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        activity('profile')->causedBy($user)->performedOn($user)->event('profile.updated')->log('Profile updated');

        return $user->refresh();
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
        activity('profile')->causedBy($user)->performedOn($user)->event('password.updated')->log('Password updated');
    }
}
