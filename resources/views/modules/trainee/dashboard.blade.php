@extends('layouts.app')

@section('content')
    @php
        $availableCourses = $availableCourses ?? collect();
        $availableCategories = $availableCategories ?? collect();
        $availableTests = $availableTests ?? collect();
        $progressByCourse = $progressByCourse ?? collect();
        $trainingNames = $trainingNames ?? [];
        $trainingTitle = fn (string $key): string => $key === '__all__' ? __('Available to everyone') : ($trainingNames[$key] ?? __('Required training'));
    @endphp

    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Good morning, ') }}{{ auth()->user()->name }}</h1>
            <nav class="mt-2" aria-label="{{ __('Breadcrumb') }}">
                <ol class="flex items-center gap-2 text-sm">
                    <li><a href="{{ route('learning.dashboard') }}" class="text-brand-500 hover:text-brand-600">{{ __('Home') }}</a></li>
                    <li class="text-gray-400" aria-hidden="true">/</li>
                    <li class="text-gray-500 dark:text-gray-400">{{ __('Course') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <nav class="mb-6 overflow-x-auto border-b border-gray-200 dark:border-gray-800" aria-label="{{ __('Trainee overview sections') }}">
        <div class="flex min-w-max gap-8">
            <a href="{{ route('learning.dashboard') }}" aria-current="page" class="border-b-2 border-brand-500 px-0.5 pb-4 text-sm font-semibold text-brand-500">{{ __('Course') }}</a>
            <a href="{{ route('learning.courses.index') }}" class="border-b-2 border-transparent px-0.5 pb-4 text-sm font-medium text-gray-500 transition hover:border-gray-300 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">{{ __('My Courses') }}</a>
            <a href="{{ route('learning.assessments.index') }}" class="border-b-2 border-transparent px-0.5 pb-4 text-sm font-medium text-gray-500 transition hover:border-gray-300 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">{{ __('Tests & Assessments') }}</a>
            @foreach (['Schedule', 'Learning Materials', 'Certificates', 'Feedback'] as $tab)
                <span aria-disabled="true" title="{{ __('Coming soon') }}" class="cursor-not-allowed border-b-2 border-transparent px-0.5 pb-4 text-sm font-medium text-gray-400 dark:text-gray-600">{{ __($tab) }}</span>
            @endforeach
        </div>
    </nav>

    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Featured Courses') }}</h2>
                <a href="{{ route('learning.catalog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('View all') }} <span aria-hidden="true" class="text-lg">→</span></a>
            </div>

            @forelse ($availableCourses->take(3) as $course)
                @if ($loop->first)
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @endif
                <x-trainee.course-card :course="$course" :progress="$progressByCourse[$course->id] ?? null" :featured="$loop->first" />
                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('No courses available yet') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Courses will appear here when they are published and available to your training enrollment.') }}</p>
                </div>
            @endforelse
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Explore by Category') }}</h2>
                <a href="{{ route('learning.catalog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('View all') }} <span aria-hidden="true" class="text-lg">→</span></a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($availableCategories as $category)
                    @php($categoryColors = [['bg' => 'bg-blue-light-50 dark:bg-blue-light-500/10', 'text' => 'text-blue-light-600'], ['bg' => 'bg-cyan-50 dark:bg-cyan-500/10', 'text' => 'text-cyan-600'], ['bg' => 'bg-theme-purple-500/10', 'text' => 'text-theme-purple-500'], ['bg' => 'bg-warning-50 dark:bg-warning-500/10', 'text' => 'text-warning-600']])
                    @php($categoryColor = $categoryColors[$loop->index % count($categoryColors)])
                    <a href="{{ route('learning.catalog.index', ['category_id' => $category->id]) }}" class="flex min-h-28 items-center gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:shadow-theme-xs dark:border-gray-800 dark:hover:border-brand-500/50">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full {{ $categoryColor['bg'] }} {{ $categoryColor['text'] }}">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 12 5l8 5.5M6.5 10v8.5h11V10M4 18.5h16M9.5 13.5h5v5h-5z"/></svg>
                        </span>
                        <span class="min-w-0 flex-1"><strong class="block text-sm font-semibold leading-5 text-gray-800 dark:text-white">{{ $category->name }}</strong><span class="mt-1 block text-sm text-gray-500">{{ $category->courses_count }} {{ Str::plural(__('Course'), $category->courses_count) }}</span></span>
                        <span class="text-2xl text-gray-400" aria-hidden="true">›</span>
                    </a>
                @empty
                    <p class="col-span-full text-sm text-gray-500">{{ __('Categories will appear as courses are published.') }}</p>
                @endforelse
            </div>
        </section>

        @if ($availableTests->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Available tests') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Assigned tests ready for you to take.') }}</p>
                    </div>
                    <a href="{{ route('learning.assessments.index') }}" class="text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('View tests') }}</a>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($availableTests->take(3) as $test)
                        <a href="{{ route('learning.assessments.index') }}" class="rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 dark:border-gray-800 dark:hover:border-brand-500/50">
                            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">{{ $trainingTitle($test->required_training_key ?: '__all__') }}</span>
                            <strong class="mt-1 block text-sm font-semibold text-gray-800 dark:text-white">{{ $test->title }}</strong>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if(isset($creditAlerts) && ($creditAlerts['eligibleCount'] ?? 0) > 0 && Route::has('learning.credit-scores.index'))
            <a href="{{ route('learning.credit-scores.index') }}" class="flex items-center justify-between gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                <span><span class="block font-semibold">{{ __('You haven’t claimed all your credit scores.') }}</span><span class="mt-1 block text-sm">{{ number_format($creditAlerts['eligibleTotal'], 2) }} {{ __('credits are ready to claim.') }}</span></span>
                <span class="rounded-lg bg-warning-500 px-4 py-2 text-sm font-medium text-white">{{ __('Review credits') }}</span>
            </a>
        @endif
    </div>
@endsection
