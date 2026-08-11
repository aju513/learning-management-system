<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ActivityRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(ActivityRepositoryInterface::class, ActivityRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });

        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers()->symbols());

        Event::listen(Login::class, function (Login $event): void {
            activity('auth')->causedBy($event->user)->event('auth.login')->log('User signed in');
        });
        Event::listen(Failed::class, function (Failed $event): void {
            activity('auth')->event('auth.failed')->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()->ip(),
            ])->log('Sign-in attempt failed');
        });
        Event::listen(Logout::class, function (Logout $event): void {
            $activity = activity('auth')->event('auth.logout');
            if ($event->user) {
                $activity->causedBy($event->user);
            }
            $activity->log('User signed out');
        });
        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            activity('auth')->causedBy($event->user)->event('auth.password-reset')->log('Password reset completed');
        });
    }
}
