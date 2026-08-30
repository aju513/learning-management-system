@extends('layouts.app')

@section('content')
    @php
        $filters = $filters ?? ['search' => '', 'status' => 'all', 'sort' => 'recent'];
        $status = $filters['status'] ?? 'all';
        $sort = $filters['sort'] ?? 'recent';
        $search = $filters['search'] ?? '';
        $filterUrl = fn (string $value): string => route('learning.courses.index', array_filter([
            'search' => $search,
            'status' => $value === 'all' ? null : $value,
            'sort' => $sort === 'recent' ? null : $sort,
        ], fn ($value): bool => $value !== null && $value !== ''));
    @endphp

    <div class="mb-7">
        <nav aria-label="{{ __('Breadcrumb') }}">
            <ol class="flex items-center gap-2 text-sm">
                <li><a href="{{ route('learning.dashboard') }}" class="text-brand-500 hover:text-brand-600">{{ __('Home') }}</a></li>
                <li class="text-gray-400" aria-hidden="true">/</li>
                <li class="text-gray-500 dark:text-gray-400">{{ __('My Courses') }}</li>
            </ol>
        </nav>
        <h1 class="mt-4 text-3xl font-semibold text-gray-900 dark:text-white">{{ __('My Courses') }}</h1>
        <p class="mt-1 text-base text-gray-600 dark:text-gray-400">{{ __('Track and continue your learning journey') }}</p>
    </div>

    <section class="mb-6">
        <form method="GET" action="{{ route('learning.courses.index') }}" class="space-y-4">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                <label class="relative block min-w-0 flex-1">
                    <span class="sr-only">{{ __('Search courses') }}</span>
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7.5"/><path stroke-linecap="round" d="m16.5 16.5 4 4"/></svg>
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search courses') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-white pl-12 pr-4 text-sm text-gray-800 shadow-theme-xs outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-white/[0.03] dark:text-white">
                </label>
                <div class="flex flex-wrap gap-3">
                    @foreach (['all' => 'All Courses', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'not_started' => 'Not Started'] as $value => $label)
                        <a href="{{ $filterUrl($value) }}" class="inline-flex h-11 items-center justify-center rounded-lg border px-5 text-sm font-medium transition {{ $status === $value ? 'border-brand-500 bg-brand-500 text-white shadow-theme-xs' : 'border-gray-300 bg-white text-gray-800 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-200 dark:hover:border-brand-500' }}" @if($status === $value) aria-current="page" @endif>{{ __($label) }}</a>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-base text-gray-800 dark:text-gray-200">{{ __('Showing :count courses', ['count' => $enrollments->count()]) }}</p>
                <label class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                    <span class="sr-only">{{ __('Sort courses') }}</span>
                    <select name="sort" onchange="this.form.submit()" class="h-11 min-w-48 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-white/[0.03] dark:text-white">
                        <option value="recent" @selected($sort === 'recent')>{{ __('Recently Added') }}</option>
                        <option value="title" @selected($sort === 'title')>{{ __('Course Name') }}</option>
                    </select>
                </label>
            </div>
        </form>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($enrollments as $enrollment)
            <x-trainee.enrolled-course-card :enrollment="$enrollment" :progress="$progressByEnrollment[$enrollment->id]" />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center dark:border-gray-700 dark:bg-white/[0.03]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-3xl text-brand-500 dark:bg-brand-500/10">📚</div>
                <h2 class="mt-5 text-lg font-semibold text-gray-800 dark:text-white">{{ $search || $status !== 'all' ? __('No courses match your filters') : __('Your learning journey starts here') }}</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">{{ $search || $status !== 'all' ? __('Try a different search or status filter.') : __('Once a course application is approved, it will appear here with your progress and next lesson.') }}</p>
                @if($search || $status !== 'all')
                    <a href="{{ route('learning.courses.index') }}" class="mt-6 inline-flex rounded-lg border border-brand-500 px-4 py-2.5 text-sm font-semibold text-brand-600 dark:text-brand-300">{{ __('Clear filters') }}</a>
                @else
                    <a href="{{ route('learning.catalog.index') }}" class="mt-6 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Explore courses') }}</a>
                @endif
            </div>
        @endforelse
    </div>
@endsection
