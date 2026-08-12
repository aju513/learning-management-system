<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                return redirect()->route('login');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('pages.auth.signin', [
            'title' => 'Sign In',
            'demoLoginEnabled' => (bool) config('lms.demo_login.enabled'),
            'demoAccounts' => config('lms.demo_login.accounts', []),
        ]));
        Fortify::requestPasswordResetLinkView(fn () => view('pages.auth.forgot-password', ['title' => 'Forgot Password']));
        Fortify::resetPasswordView(fn (Request $request) => view('pages.auth.reset-password', [
            'request' => $request,
            'title' => 'Reset Password',
        ]));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', Str::lower((string) $request->email))->first();

            return $user?->isActive() && password_verify((string) $request->password, $user->password)
                ? $user
                : null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('demo-login', fn (Request $request) => Limit::perMinute(12)->by($request->ip()));
    }
}
