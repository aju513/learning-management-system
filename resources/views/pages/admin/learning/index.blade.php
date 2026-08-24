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
    <p class="mt-2 max-w-2xl text-sm text-white/80">Continue where you left off or revisit a completed course from your latest enrollments.</p>
</div>

<div class="space-y-4">
    @forelse($enrollments as $enrollment)
        @php
            $materials = $enrollment->course->modules->flatMap->chapters->flatMap->materials->values();
            $requiredMaterials = $materials->where('is_required', true);
            $lessonMaterials = $requiredMaterials->filter(fn ($item) => $item->type !== \App\Enums\MaterialType::CourseAssessment);
            $completedIds = $enrollment->materialProgress->whereNotNull('completed_at')->pluck('learning_material_id');
            $completedLessons = $lessonMaterials->whereIn('id', $completedIds)->count();
            $totalLessons = $lessonMaterials->count();
            $remainingLessons = max(0, $totalLessons - $completedLessons);
            $nextMaterial = $materials->first(fn ($item) => ! $completedIds->contains($item->id)) ?? $materials->first();
            $progress = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : (float) $enrollment->progress_percentage;
            $assessmentMaterial = $materials->first(fn ($item) => $item->type === \App\Enums\MaterialType::CourseAssessment);
            $assessment = $assessmentMaterial?->courseAssessment;
            $traineeAttempts = $assessment?->attempts?->where('user_id', auth()->id()) ?? collect();
            $assessmentPassed = $traineeAttempts->contains(fn ($attempt) => (bool) $attempt->passed);
            $assessmentLocked = (bool) $assessment && $remainingLessons > 0;
            $assessmentStatus = ! $assessment ? null : ($assessmentPassed ? 'Passed' : ($assessmentLocked ? 'Locked' : 'Available'));
            $primaryAction = $assessment && ! $assessmentPassed && ! $assessmentLocked
                ? 'Take assessment'
                : ($progress >= 100 ? 'Review course' : ($enrollment->started_at ? 'Continue course' : 'Start course'));
        @endphp
        <article x-data="{ opening: false }" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="grid lg:grid-cols-[260px_1fr]">
                <div class="relative min-h-[180px] overflow-hidden bg-brand-50 dark:bg-brand-500/10">
                    @if($enrollment->course->thumbnail_path)
                        <img src="{{ Storage::disk('public')->url($enrollment->course->thumbnail_path) }}" alt="" class="h-full min-h-[180px] w-full object-cover">
                    @else
                        <div class="flex h-full min-h-[180px] items-center justify-center bg-gradient-to-br from-brand-100 to-cyan-100 text-6xl font-bold text-brand-500 dark:from-brand-500/20 dark:to-cyan-500/20">{{ Str::upper(Str::substr($enrollment->course->title, 0, 1)) }}</div>
                    @endif
                    <div class="absolute left-4 top-4"><x-ui.badge :color="$enrollment->status->value === 'completed' ? 'success' : 'primary'">{{ ucfirst($enrollment->status->value) }}</x-ui.badge></div>
                </div>
                <div class="flex flex-col justify-between gap-5 p-5 sm:p-6">
                    <div>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-500">{{ $enrollment->course->category?->name ?? 'Course' }}</p>
                                <h2 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white"><a href="{{ route('learning.courses.player', $enrollment) }}" class="hover:text-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">{{ $enrollment->course->title }}</a></h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $enrollment->course->instructor?->name ?? 'Instructor pending' }} · Enrolled {{ $enrollment->enrolled_at?->format('M d, Y') }}</p>
                            </div>
                            <span class="text-2xl font-bold text-brand-500">{{ $progress }}%</span>
                        </div>
                        <p class="mt-4 max-w-3xl text-sm text-gray-500">{{ Str::limit($enrollment->course->short_description, 150) }}</p>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-xs text-gray-500">
                            <span>{{ $completedLessons }} of {{ $totalLessons }} required lessons complete</span>
                            @if($remainingLessons > 0)
                                <span>{{ $remainingLessons }} {{ Str::plural('lesson', $remainingLessons) }} left</span>
                            @else
                                <span class="font-medium text-success-600">Course complete</span>
                            @endif
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-500" style="width: {{ min(100, max(0, $progress)) }}%"></div></div>
                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-500">
                            <span><strong class="text-gray-700 dark:text-gray-200">{{ $completedLessons }}</strong> completed</span>
                            <span><strong class="text-gray-700 dark:text-gray-200">{{ $totalLessons }}</strong> total lessons</span>
                            <span><strong class="text-gray-700 dark:text-gray-200">{{ $remainingLessons }}</strong> remaining</span>
                            @if($assessmentStatus)
                                <span>Assessment: <strong class="{{ $assessmentPassed ? 'text-success-600' : ($assessmentLocked ? 'text-warning-600' : 'text-brand-600') }}">{{ $assessmentStatus }}</strong> {{ $assessment?->questions?->count() ?? 0 }} questions</span>
                            @endif
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            @if($nextMaterial && $assessment && ! $assessmentPassed && ! $assessmentLocked)
                                <form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$enrollment, $assessmentMaterial]) }}" @submit="opening = true">
                                    @csrf
                                    <button type="submit" :disabled="opening" class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white disabled:cursor-wait disabled:opacity-70" aria-label="Take course assessment">
                                        <span x-show="!opening">Take assessment <span class="ml-2">→</span></span><span x-cloak x-show="opening">Opening assessment…</span>
                                    </button>
                                </form>
                            @elseif($nextMaterial)
                                <a href="{{ route('learning.courses.player', $enrollment) }}" @click="opening = true" class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2" :class="opening ? 'cursor-wait opacity-70' : ''"><span x-show="!opening">{{ $primaryAction }} <span class="ml-2">→</span></span><span x-cloak x-show="opening">Opening course…</span></a>
                            @else
                                <span class="text-sm text-gray-500">Course curriculum is being prepared.</span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $enrollment->course->estimated_duration_minutes }} min · {{ $enrollment->course->difficulty }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 p-14 text-center dark:border-gray-700">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-3xl text-brand-500 dark:bg-brand-500/10">→</div>
            <h2 class="mt-5 text-lg font-semibold text-gray-800 dark:text-white">Your learning journey starts here</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">Once a course application is approved, it will appear here with your progress and next lesson.</p>
            <a href="{{ route('learning.catalog.index') }}" class="mt-6 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Explore courses</a>
        </div>
    @endforelse
</div>
@endsection
