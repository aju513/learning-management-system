@extends('layouts.fullscreen-layout')

@section('content')
<div class="relative flex min-h-screen items-center justify-center bg-white px-4 py-12 dark:bg-gray-900">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <img src="/images/logo/logo-icon.svg" class="mx-auto mb-4" width="44" height="44" alt="{{ config('app.name') }}">
            <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">Admin sign in</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Enter your account credentials to continue.</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-error-500/30 bg-error-50 p-3 text-sm text-error-600 dark:bg-error-500/10">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="mb-5 rounded-lg border border-success-500/30 bg-success-50 p-3 text-sm text-success-600 dark:bg-success-500/10">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"><input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-brand-500"> Remember me</label>
                    <a href="{{ route('password.request') }}" class="text-sm text-brand-500 hover:text-brand-600">Forgot password?</a>
                </div>
                <button type="submit" class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Sign in</button>
            </form>
        </div>
    </div>
</div>
@endsection
