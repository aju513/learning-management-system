@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Enrolled Courses">
    <x-slot:actions>
        <a href="{{ route('learning.catalog.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Explore catalog</a>
    </x-slot:actions>
</x-common.page-breadcrumb>

<div class="mb-6 rounded-2xl bg-gradient-to-r from-brand-600 to-cyan-600 p-6 text-white">
    <p class="text-sm text-white/75">Your learning space</p>
    <h1 class="mt-1 text-2xl font-bold">Keep learning, one lesson at a time</h1>
    <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">Continue where you left off or revisit a completed course from your latest enrollments.</p>
</div>

<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($enrollments as $enrollment)
        @php
            $courseProgress = $progressByEnrollment[$enrollment->id];
            $nextMaterial = $courseProgress['nextMaterial'];
            $assessmentMaterial = $courseProgress['assessmentMaterial'];
            $assessment = $courseProgress['assessment'];
            $assessmentPassed = $courseProgress['assessmentPassed'];
            $assessmentLocked = $assessment && $courseProgress['remainingLessons'] > 0;
            $assessmentStatus = $courseProgress['assessmentStatus'];
            $primaryAction = $assessment && ! $assessmentPassed && ! $assessmentLocked
                ? 'Take assessment'
                : ($courseProgress['isComplete'] ? 'Review course' : ($enrollment->started_at ? 'Continue course' : 'Start course'));
        @endphp
        <article x-data="{ opening: false }" class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="relative min-h-[170px] overflow-hidden bg-brand-50 dark:bg-brand-500/10">
                @if($enrollment->course->thumbnail_path)
                    <img src="{{ Storage::disk('public')->url($enrollment->course->thumbnail_path) }}" alt="" class="h-full min-h-[170px] w-full object-cover">
                @else
                    <div class="flex h-full min-h-[170px] items-center justify-center bg-gradient-to-br from-brand-100 to-cyan-100 text-6xl font-bold text-brand-500 dark:from-brand-500/20 dark:to-cyan-500/20">{{ Str::upper(Str::substr($enrollment->course->title, 0, 1)) }}</div>
                @endif
                <div class="absolute left-4 top-4"><x-ui.badge :color="$enrollment->status->value === 'completed' ? 'success' : 'primary'">{{ ucfirst($enrollment->status->value) }}</x-ui.badge></div>
            </div>
            <div class="flex flex-1 flex-col justify-between gap-5 p-5 sm:p-6">
                <div>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-500">{{ $enrollment->course->category?->name ?? 'Course' }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white"><a href="{{ route('learning.courses.player', $enrollment) }}" class="hover:text-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">{{ $enrollment->course->title }}</a></h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $enrollment->course->instructor?->name ?? 'Instructor pending' }} · Enrolled {{ $enrollment->enrolled_at?->format('M d, Y') }}</p>
                        </div>
                        <span class="text-2xl font-bold text-brand-500">{{ $courseProgress['percentage'] }}%</span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ Str::limit($enrollment->course->short_description, 150) }}</p>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3 text-xs text-gray-600 dark:text-gray-400">
                        <span>{{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} required items complete · {{ $courseProgress['completedLessons'] }} of {{ $courseProgress['totalLessons'] }} lessons</span>
                        @if($courseProgress['remaining'] > 0)
                            <span>{{ $courseProgress['remaining'] }} {{ Str::plural('item', $courseProgress['remaining']) }} left</span>
                        @else
                            <span class="font-medium text-success-600">Course complete</span>
                        @endif
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-500" style="width: {{ min(100, max(0, $courseProgress['percentage'])) }}%"></div></div>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]"><strong class="block text-lg text-gray-800 dark:text-white">{{ $courseProgress['completedLessons'] }}</strong><span class="text-[11px] text-gray-600 dark:text-gray-400">Completed lessons</span></div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]"><strong class="block text-lg text-gray-800 dark:text-white">{{ $courseProgress['totalLessons'] }}</strong><span class="text-[11px] text-gray-600 dark:text-gray-400">Total lessons</span></div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]"><strong class="block text-lg text-gray-800 dark:text-white">{{ $courseProgress['remainingLessons'] }}</strong><span class="text-[11px] text-gray-600 dark:text-gray-400">Remaining lessons</span></div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]"><strong class="block text-sm {{ $assessmentPassed ? 'text-success-600' : ($assessmentLocked ? 'text-warning-600' : 'text-brand-600') }}">{{ $assessmentStatus ?? 'None' }}</strong><span class="text-[11px] text-gray-600 dark:text-gray-400">Assessment</span></div>
                    </div>
                    @if($courseProgress['creditPoints'] > 0)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-100 bg-brand-50 px-3 py-2.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                            <span class="text-xs text-brand-700 dark:text-brand-300">Course credit score <strong class="ml-1 text-sm">+{{ number_format($courseProgress['creditPoints'], 2) }}</strong></span>
                            @if($courseProgress['creditAward']?->isClaimed())
                                <span class="text-xs font-semibold text-success-600">Claimed</span>
                            @elseif($courseProgress['creditAward'])
                                <form method="POST" action="{{ route('learning.credit-scores.claim', $courseProgress['creditAward']) }}">@csrf<button type="submit" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white">Claim credit</button></form>
                            @elseif($enrollment->status->value === 'completed')
                                <form method="POST" action="{{ route('learning.credit-scores.course-claim', $enrollment) }}">@csrf<button type="submit" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white">Claim credit</button></form>
                            @else
                                <span class="text-xs text-gray-600 dark:text-gray-400">Earn after course completion</span>
                            @endif
                        </div>
                    @endif
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        @if($nextMaterial && $assessment && ! $assessmentPassed && ! $assessmentLocked)
                            <form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$enrollment, $assessmentMaterial]) }}" @submit="opening = true">
                                @csrf
                                <button type="submit" :disabled="opening" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white disabled:cursor-wait disabled:opacity-70"><span x-show="!opening"><i class="bi bi-clipboard-check mr-1" aria-hidden="true"></i>Take assessment <span class="ml-1">→</span></span><span x-cloak x-show="opening">Opening assessment…</span></button>
                            </form>
                        @elseif($nextMaterial)
                            <a href="{{ $courseProgress['isComplete'] ? route('learning.courses.summary', $enrollment) : route('learning.courses.player', $enrollment) }}" @click="opening = true" class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2" :class="opening ? 'cursor-wait opacity-70' : ''"><span x-show="!opening">{{ $primaryAction }} <span class="ml-2">→</span></span><span x-cloak x-show="opening">Opening course…</span></a>
                        @else
                            <span class="text-sm text-gray-600 dark:text-gray-400">Course curriculum is being prepared.</span>
                        @endif
                        <span class="text-xs text-gray-500">{{ $enrollment->course->estimated_duration_minutes }} min · {{ ucfirst($enrollment->course->difficulty) }}</span>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-14 text-center dark:border-gray-700">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-3xl text-brand-500 dark:bg-brand-500/10">→</div>
            <h2 class="mt-5 text-lg font-semibold text-gray-800 dark:text-white">Your learning journey starts here</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">Once a course application is approved, it will appear here with your progress and next lesson.</p>
            <a href="{{ route('learning.catalog.index') }}" class="mt-6 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Explore courses</a>
        </div>
    @endforelse
</div>
@endsection
