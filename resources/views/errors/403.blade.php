@extends('layouts.fullscreen-layout')

@section('content')
<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 dark:bg-gray-950">
    <div class="w-full max-w-lg rounded-2xl border border-warning-200 bg-white p-8 text-center shadow-theme-sm dark:border-warning-500/30 dark:bg-white/[0.03] sm:p-10">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-warning-50 text-2xl font-bold text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">403</div>
        <h1 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">{{ __('This action is not available') }}</h1>
        <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ __('You do not have access to this page or the requested item may not be available in your current course or role.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('portal.home') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">{{ __('Go back') }}</a>
            <a href="{{ route('portal.home') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ __('Return to dashboard') }}</a>
        </div>
    </div>
</div>
@endsection
