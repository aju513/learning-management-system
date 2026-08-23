@extends('layouts.app')
@section('content')
    @php
        $courseGroups = $availableCourses->groupBy(fn ($course) => $course->required_training_key ?: '__all__');
        $testGroups = $availableTests->groupBy(fn ($test) => $test->required_training_key ?: '__all__');
        $trainingTitle = fn (string $key): string => $key === '__all__' ? 'Available to everyone' : ($trainingNames[$key] ?? 'Required training');
    @endphp

    <x-common.page-breadcrumb :pageTitle="$title">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('learning.catalog.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Browse courses</a>
                <a href="{{ route('learning.assessments.index') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">View tests</a>
            </div>
        </x-slot:actions>
    </x-common.page-breadcrumb>

    <div class="mb-6 rounded-2xl bg-gradient-to-r from-brand-500 to-brand-700 p-6 text-white">
        <p class="text-sm text-white/75">{{ $context }}</p>
        <h1 class="mt-1 text-2xl font-bold">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-2 max-w-2xl text-sm text-white/80">Start a course or test that is available through your training enrollment.</p>
    </div>

    @if(isset($creditAlerts) && $creditAlerts['eligibleCount'] > 0)
        <a href="{{ route('learning.credit-scores.index') }}" class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
            <span><span class="block font-semibold">You haven’t claimed all your credit scores.</span><span class="mt-1 block text-sm">{{ number_format($creditAlerts['eligibleTotal'], 2) }} credits are ready to claim for {{ $creditAlerts['fiscalYear']?->name }}.</span></span>
            <span class="rounded-lg bg-warning-500 px-4 py-2 text-sm font-medium text-white">Review credits</span>
        </a>
    @endif

    <div class="mt-6 space-y-6">
        <x-common.component-card title="Available courses" desc="Courses are grouped by the training enrollment that makes them available.">
            <div class="space-y-7">
                @forelse ($courseGroups as $trainingKey => $courses)
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-500">Training access</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $trainingTitle($trainingKey) }}</h2>
                            </div>
                            <x-ui.badge color="light">{{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}</x-ui.badge>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($courses as $course)
                                @php($application = $course->enrollments->first())
                                <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ $course->title }}</h3>
                                        @if($course->category)<x-ui.badge color="primary">{{ $course->category->name }}</x-ui.badge>@endif
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">{{ Str::limit($course->short_description, 100) }}</p>
                                    <p class="mt-3 text-xs text-gray-400">{{ ucfirst($course->difficulty) }} · {{ $course->estimated_duration_minutes }} min · {{ $course->modules_count }} modules</p>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <a href="{{ route('learning.catalog.show', $course) }}" class="text-sm font-medium text-brand-500">View course</a>
                                        @if($application && in_array($application->status->value, ['active', 'completed'], true))
                                            <x-ui.badge color="success">Enrolled</x-ui.badge>
                                        @elseif($application?->status->value === 'pending')
                                            <x-ui.badge color="warning">Pending</x-ui.badge>
                                        @else
                                            <span class="text-xs text-gray-400">Apply from details</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700">
                        <h2 class="font-semibold text-gray-800 dark:text-white">No courses available yet</h2>
                        <p class="mt-1 text-sm text-gray-500">Courses will appear here when they are published and available to your training enrollment.</p>
                    </div>
                @endforelse
            </div>
        </x-common.component-card>

        <x-common.component-card title="Available tests" desc="Assigned tests are grouped by the training enrollment that makes them available.">
            <div class="space-y-7">
                @forelse ($testGroups as $trainingKey => $tests)
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-500">Training access</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $trainingTitle($trainingKey) }}</h2>
                            </div>
                            <x-ui.badge color="light">{{ $tests->count() }} {{ Str::plural('test', $tests->count()) }}</x-ui.badge>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($tests as $test)
                                <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ $test->title }}</h3>
                                        <x-ui.badge color="success">Available</x-ui.badge>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">{{ Str::limit($test->description, 100) }}</p>
                                    <p class="mt-3 text-xs text-gray-400">{{ $test->duration_minutes }} min · Pass {{ $test->passing_percentage }}% · {{ $test->questions_count }} questions</p>
                                    <form method="POST" action="{{ route('learning.assessments.start', $test) }}" class="mt-4">@csrf<button class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Start / continue test</button></form>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700">
                        <h2 class="font-semibold text-gray-800 dark:text-white">No tests available yet</h2>
                        <p class="mt-1 text-sm text-gray-500">Assigned tests will appear here when they are published and available to your training enrollment.</p>
                    </div>
                @endforelse
            </div>
        </x-common.component-card>
    </div>
@endsection
