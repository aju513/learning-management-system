@extends('layouts.trainee.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Course summary">
    <x-slot:actions>
        <a href="{{ route('learning.courses.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Back to courses</a>
    </x-slot:actions>
</x-common.page-breadcrumb>

<div class="mx-auto max-w-6xl">
    <div class="grid items-start gap-6 lg:grid-cols-12">
        <div class="min-w-0 space-y-4 lg:col-span-8">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-brand-600 to-cyan-600 p-6 text-white shadow-theme-sm sm:p-8">
        <p class="text-sm font-medium text-white/75">Course completed</p>
        <h1 class="mt-2 text-3xl font-bold">{{ $enrollment->course->title }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">You have completed every required course item. Review your learning materials or revisit your assessment result below.</p>
    </section>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $progress['completed'] }}</p><p class="mt-1 text-sm text-gray-500">Course items complete</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $progress['totalLessons'] }}</p><p class="mt-1 text-sm text-gray-500">Learning materials</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-2xl font-bold text-success-600">{{ $progress['percentage'] }}%</p><p class="mt-1 text-sm text-gray-500">Course progress</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-2xl font-bold {{ $progress['assessmentPassed'] ? 'text-success-600' : 'text-gray-900 dark:text-white' }}">{{ $progress['assessment'] ? ($progress['assessmentPassed'] ? 'Passed' : 'Complete') : '—' }}</p><p class="mt-1 text-sm text-gray-500">Assessment</p></div>
    </div>

    @if($progress['creditPoints'] > 0)
        <section class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">Course credit score</p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">+{{ number_format($progress['creditPoints'], 2) }} credits</h2>
                    @if($progress['creditAward']?->isClaimed())
                        <p class="mt-2 text-sm font-medium text-success-700 dark:text-success-300">This credit score has already been claimed.</p>
                    @elseif($progress['creditAward'])
                        <p class="mt-2 text-sm text-brand-800 dark:text-brand-200">Your course is complete. Claim your credit score now.</p>
                    @elseif($enrollment->status->value === 'completed')
                        <p class="mt-2 text-sm text-brand-800 dark:text-brand-200">Your course was completed earlier. Claim your course credit score now.</p>
                    @else
                        <p class="mt-2 text-sm text-brand-800 dark:text-brand-200">Your credit score is being prepared and will be available shortly.</p>
                    @endif
                </div>
                @if($progress['creditAward'] && ! $progress['creditAward']->isClaimed())
                    <form method="POST" action="{{ route('learning.credit-scores.claim', $progress['creditAward']) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">Claim {{ number_format($progress['creditPoints'], 2) }} credits</button>
                    </form>
                @elseif($enrollment->status->value === 'completed')
                    <form method="POST" action="{{ route('learning.credit-scores.course-claim', $enrollment) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">Claim {{ number_format($progress['creditPoints'], 2) }} credits</button>
                    </form>
                @endif
            </div>
        </section>
    @endif

    <div class="space-y-6">
        <x-common.component-card title="Review your course" desc="Choose where you want to continue reviewing this completed course.">
            <div class="space-y-3">
                @if($progress['lastViewed'])
                    <a href="{{ route('learning.courses.materials.show', [$enrollment, $progress['lastViewed']]) }}" class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-gray-800 dark:hover:bg-brand-500/10"><span><span class="block text-sm font-semibold text-gray-800 dark:text-white">Revisit last viewed material</span><span class="mt-1 block text-xs text-gray-500">{{ $progress['lastViewed']->title }}</span></span><span class="text-brand-500">→</span></a>
                @endif
                @if($progress['latestAssessmentAttempt'])
                    <a href="{{ route('learning.course-assessment-attempts.show', [$enrollment, $progress['latestAssessmentAttempt']]) }}" class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-gray-800 dark:hover:bg-brand-500/10"><span><span class="block text-sm font-semibold text-gray-800 dark:text-white">Review assessment</span><span class="mt-1 block text-xs text-gray-500">{{ number_format((float) $progress['latestAssessmentAttempt']->score_percentage, 0) }}% · Attempt {{ $progress['latestAssessmentAttempt']->attempt_number }}</span></span><span class="text-brand-500">→</span></a>
                @endif
                <a href="{{ route('learning.courses.materials.show', [$enrollment, $progress['materials']->first()]) }}" class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-brand-50 dark:border-gray-800 dark:hover:bg-brand-500/10"><span><span class="block text-sm font-semibold text-gray-800 dark:text-white">Start from beginning</span><span class="mt-1 block text-xs text-gray-500">Open the first course item intentionally.</span></span><span class="text-brand-500">→</span></a>
            </div>
        </x-common.component-card>

        <x-common.component-card title="Assessment result" desc="Your most recent course assessment attempt.">
            @if($progress['latestAssessmentAttempt'])
                <div class="rounded-xl bg-success-50 p-5 dark:bg-success-500/10"><p class="text-sm font-semibold text-success-700 dark:text-success-300">Assessment {{ $progress['assessmentPassed'] ? 'passed' : 'completed' }}</p><p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format((float) $progress['latestAssessmentAttempt']->score_percentage, 0) }}%</p><p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $progress['latestAssessmentAttempt']->earned_marks }} / {{ $progress['latestAssessmentAttempt']->total_marks }} marks · Passing score {{ $progress['assessment']->passing_percentage }}%</p><p class="mt-2 text-xs text-gray-500">Completed {{ $progress['latestAssessmentAttempt']->submitted_at?->format('F j, Y') ?? '—' }}</p></div>
                <a href="{{ route('learning.course-assessment-attempts.show', [$enrollment, $progress['latestAssessmentAttempt']]) }}" class="mt-4 inline-flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Review answers</a>
            @else
                <p class="text-sm text-gray-500">No assessment result is available for this course.</p>
            @endif
        </x-common.component-card>
    </div>
        </div>

        <aside class="min-w-0 lg:col-span-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Suggested courses</h2>
                        <p class="mt-1 text-sm text-gray-500">Continue your learning journey.</p>
                    </div>
                    <a href="{{ route('learning.catalog.index') }}" class="shrink-0 text-sm font-semibold text-brand-500 hover:text-brand-600">View all</a>
                </div>

                <div class="space-y-3">
                    @forelse ($suggestedCourses->take(4) as $course)
                        <a href="{{ route('learning.catalog.show', $course) }}" class="group flex h-24 gap-3 rounded-xl border border-gray-200 p-3 transition hover:border-brand-300 hover:shadow-theme-xs dark:border-gray-800 dark:hover:border-brand-500/50">
                            <div class="h-full w-20 shrink-0 overflow-hidden rounded-lg bg-brand-50 dark:bg-brand-500/10">
                                @if ($course->thumbnail_path)
                                    <img src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-brand-100 via-blue-light-50 to-cyan-100 text-2xl font-bold text-brand-500 dark:from-brand-500/20 dark:via-blue-light-500/10 dark:to-cyan-500/10">{{ Str::upper(Str::substr($course->title, 0, 1)) }}</div>
                                @endif
                            </div>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold text-brand-500">{{ $course->category?->name ?? 'Course' }}</span>
                                <span class="mt-1 block line-clamp-2 text-sm font-semibold leading-5 text-gray-900 group-hover:text-brand-500 dark:text-white">{{ $course->title }}</span>
                                <span class="mt-1 block truncate text-xs text-gray-500">{{ $course->instructor?->name ?? 'Instructor pending' }} · {{ $course->estimated_duration_minutes }} min</span>
                            </span>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-5 text-center dark:border-gray-700">
                            <p class="text-sm text-gray-500">No other courses are available right now.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
