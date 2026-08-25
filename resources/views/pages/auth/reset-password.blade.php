@extends('layouts.fullscreen-layout')

@section('content')
<div class="flex min-h-screen items-center justify-center bg-white px-4 dark:bg-gray-900">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 p-8 dark:border-gray-800 dark:bg-white/[0.03]">
        <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">{{ __('Choose a new password') }}</h1>
        @if ($errors->any())<div class="my-4 rounded-lg bg-error-50 p-3 text-sm text-error-600">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Email') }}</label><input name="email" type="email" required value="{{ old('email', $request->email) }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Password') }}</label><input name="password" type="password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Confirm password') }}</label><input name="password_confirmation" type="password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
            <button class="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600">{{ __('Reset password') }}</button>
        </form>
    </div>
</div>
@endsection
