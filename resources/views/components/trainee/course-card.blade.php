@props([
    'course',
    'progress' => null,
    'featured' => false,
])

@php
    $application = $course->enrollments->first();
    $enrolled = $application?->status?->grantsLearningAccess() ?? false;
    $percentage = (int) ($progress['percentage'] ?? 0);
    $lessonCount = $course->relationLoaded('modules')
        ? $course->modules->flatMap->chapters->flatMap->materials->count()
        : ($course->modules_count ?? 0);
    $duration = (int) $course->estimated_duration_minutes;
    $hours = intdiv($duration, 60);
    $minutes = $duration % 60;
    $durationLabel = $hours > 0 ? $hours.'h'.($minutes > 0 ? ' '.$minutes.'m' : '') : $minutes.'m';
    $actionUrl = $enrolled
        ? (($progress['isComplete'] ?? false) ? route('learning.courses.summary', $application) : route('learning.courses.player', $application))
        : route('learning.catalog.show', $course);
    $actionLabel = $enrolled
        ? (($progress['isComplete'] ?? false) ? __('Review course') : (($application->started_at ?? false) || $percentage > 0 ? __('Continue learning') : __('Start learning')))
        : __('View course');
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border bg-white transition hover:-translate-y-0.5 hover:shadow-theme-md dark:bg-white/[0.03] {{ $featured ? 'border-brand-500 shadow-theme-md' : 'border-gray-200 dark:border-gray-800' }}">
    <div class="relative h-40 overflow-hidden bg-brand-50 dark:bg-brand-500/10">
        @if ($course->thumbnail_path)
            <img src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center bg-gradient-to-br from-brand-100 via-blue-light-50 to-cyan-100 text-6xl font-bold text-brand-500 dark:from-brand-500/20 dark:via-blue-light-500/10 dark:to-cyan-500/10">{{ Str::upper(Str::substr($course->title, 0, 1)) }}</div>
        @endif
        <span class="absolute left-4 top-4 rounded-full bg-brand-700 px-3 py-1 text-xs font-semibold text-white shadow-sm">{{ $course->category?->name ?? __('Course') }}</span>
    </div>

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <h3 class="line-clamp-2 min-h-12 text-lg font-semibold leading-6 text-gray-900 dark:text-white">
            <a href="{{ $actionUrl }}" class="outline-none transition hover:text-brand-500 focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $course->title }}</a>
        </h3>

        <div class="mt-3 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ Str::upper(Str::substr($course->instructor?->name ?? 'I', 0, 1)) }}</span>
            <span>{{ $course->instructor?->name ?? __('Instructor pending') }}</span>
        </div>

        <div class="mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.75A2.75 2.75 0 0 1 6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v12.5A2.75 2.75 0 0 1 17.25 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75Z"/><path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h5"/></svg>
            <span>{{ $lessonCount }} {{ Str::plural(__('Lesson'), $lessonCount) }}</span>
            <span aria-hidden="true">·</span>
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
            <span>{{ $durationLabel }}</span>
        </div>

        <div class="mt-auto pt-5">
            <div class="flex items-center gap-3">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800" role="progressbar" aria-label="{{ __('Course progress') }}" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-warning-500 transition-all" style="width: {{ min(100, max(0, $percentage)) }}%"></div>
                </div>
                <span class="shrink-0 text-sm font-medium {{ $enrolled ? 'text-warning-600 dark:text-warning-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $enrolled ? $percentage.'% '.__('learned') : __('Not started') }}</span>
            </div>

            @if ($enrolled || $featured)
                <a href="{{ $actionUrl }}" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">{{ $actionLabel }}</a>
            @endif
        </div>
    </div>
</article>
