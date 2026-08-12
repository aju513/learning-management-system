<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DemoLoginService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function authenticate(Request $request, string $account): User
    {
        $configuredAccount = config("lms.demo_login.accounts.{$account}");
        $email = is_array($configuredAccount) ? ($configuredAccount['email'] ?? null) : null;
        $user = is_string($email) ? $this->users->findActiveByEmail($email) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'account' => 'This demo account is unavailable. Run the local database seeder and try again.',
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        activity('auth')
            ->causedBy($user)
            ->event('auth.demo-login')
            ->withProperties(['account' => $account])
            ->log('User signed in with a demo account shortcut');

        return $user;
    }
}
