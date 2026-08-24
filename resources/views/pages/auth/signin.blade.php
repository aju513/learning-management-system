@extends('layouts.fullscreen-layout')

@section('content')
<div class="relative flex min-h-screen items-center justify-center bg-white px-4 py-12 dark:bg-gray-900">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <img src="/images/logo/logo-icon.svg" class="mx-auto mb-4" width="44" height="44" alt="{{ config('app.name') }}">
            <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">{{ __('LMS sign in') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Enter your account credentials to continue.') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-error-500/30 bg-error-50 p-3 text-sm text-error-600 dark:bg-error-500/10">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="mb-5 rounded-lg border border-success-500/30 bg-success-50 p-3 text-sm text-success-600 dark:bg-success-500/10">{{ session('status') }}</div>
            @endif
            @if ($demoLoginEnabled)
                <div class="mb-6">
                    <div class="mb-3 text-center">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Quick demo access') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Choose a seeded account to sign in instantly.') }}</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($demoAccounts as $account => $details)
                            <form method="POST" action="{{ route('admin.demo-login') }}">
                                @csrf
                                <input type="hidden" name="account" value="{{ $account }}">
                                <button type="submit" class="flex w-full flex-col items-center rounded-lg border border-brand-200 bg-brand-50 px-3 py-3 text-center transition hover:border-brand-300 hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:hover:bg-brand-500/20">
                                    <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ __($details['label']) }}</span>
                                    <span class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $details['email'] }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
                <div class="mb-6 flex items-center gap-3">
                    <span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>
                    <span class="text-xs uppercase tracking-wide text-gray-400">{{ __('or use credentials') }}</span>
                    <span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>
                </div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"><input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-brand-500"> {{ __('Remember me') }}</label>
                    <a href="{{ route('password.request') }}" class="text-sm text-brand-500 hover:text-brand-600">{{ __('Forgot password?') }}</a>
                </div>
                <button type="submit" class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">{{ __('Sign in') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
