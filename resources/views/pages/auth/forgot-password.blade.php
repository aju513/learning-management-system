@extends('layouts.fullscreen-layout')

@section('content')
<div class="flex min-h-screen items-center justify-center bg-white px-4 dark:bg-gray-900">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 p-8 dark:border-gray-800 dark:bg-white/[0.03]">
        <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">Reset your password</h1>
        <p class="mb-6 mt-2 text-sm text-gray-500">We will email you a secure password-reset link.</p>
        @if (session('status'))<div class="mb-4 rounded-lg bg-success-50 p-3 text-sm text-success-600">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-lg bg-error-50 p-3 text-sm text-error-600">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label><input name="email" type="email" required value="{{ old('email') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
            <button class="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600">Email reset link</button>
        </form>
        <a href="{{ route('login') }}" class="mt-5 block text-center text-sm text-brand-500">Back to sign in</a>
    </div>
</div>
@endsection
