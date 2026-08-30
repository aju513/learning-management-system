@props([
    'enrollment',
    'progress',
])

@php
    $course = $enrollment->course;
    $isComplete = $enrollment->status->value === 'completed' || ($progress['isComplete'] ?? false);
    $percentage = $isComplete ? 100 : (int) ($progress['percentage'] ?? 0);
    $lessonCount = $progress['totalLessons'] ?? ($course->modules->flatMap->chapters->flatMap->materials->count());
    $duration = (int) $course->estimated_duration_minutes;
    $hours = intdiv($duration, 60);
    $minutes = $duration % 60;
    $durationLabel = $hours > 0 ? $hours.'h'.($minutes > 0 ? ' '.$minutes.'m' : '') : $minutes.'m';
    $actionUrl = $isComplete ? route('learning.courses.summary', $enrollment) : route('learning.courses.player', $enrollment);
@endphp

<article class="group flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/40">
    <div class="flex min-w-0 gap-4">
        <div class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg bg-brand-50 dark:bg-brand-500/10">
            @if ($course->thumbnail_path)
                <img src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center bg-gradient-to-br from-brand-100 via-blue-light-50 to-cyan-100 text-4xl font-bold text-brand-500 dark:from-brand-500/20 dark:via-blue-light-500/10 dark:to-cyan-500/10">{{ Str::upper(Str::substr($course->title, 0, 1)) }}</div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <span class="inline-flex max-w-full rounded-full bg-blue-light-50 px-3 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">{{ $course->category?->name ?? __('Course') }}</span>
            <h2 class="mt-2 line-clamp-2 text-base font-semibold leading-5 text-gray-900 dark:text-white">
                <a href="{{ $actionUrl }}" class="outline-none transition hover:text-brand-500 focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $course->title }}</a>
            </h2>
            <p class="mt-2 flex items-center gap-2 truncate text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                {{ $course->instructor?->name ?? __('Instructor pending') }}
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.75A2.75 2.75 0 0 1 6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v12.5A2.75 2.75 0 0 1 17.25 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75Z"/><path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h5"/></svg>{{ $lessonCount }} {{ Str::plural(__('Lesson'), $lessonCount) }}</span>
                <span aria-hidden="true">·</span>
                <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>{{ $durationLabel }}</span>
            </div>
        </div>
    </div>

    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
        <div class="flex items-center gap-3 text-sm">
            <span class="shrink-0 text-gray-700 dark:text-gray-300">{{ $isComplete || $percentage > 0 ? __('Progress') : __('Not started') }}</span>
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800" role="progressbar" aria-label="{{ __('Course progress') }}" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-warning-500 transition-all" style="width: {{ min(100, max(0, $percentage)) }}%"></div>
            </div>
            <strong class="shrink-0 text-gray-900 dark:text-white">{{ $percentage }}%</strong>
        </div>

        @if (($progress['creditPoints'] ?? 0) > 0)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-brand-100 bg-brand-50 px-3 py-2 text-xs dark:border-brand-500/20 dark:bg-brand-500/10">
                <span class="text-brand-700 dark:text-brand-300">{{ __('Course credit score') }} <strong class="ml-1">+{{ number_format($progress['creditPoints'], 2) }}</strong></span>
                @if ($progress['creditAward']?->isClaimed())
                    <x-ui.badge color="success" size="sm">{{ __('Credit claimed') }}</x-ui.badge>
                @elseif ($progress['creditAward'])
                    <form method="POST" action="{{ route('learning.credit-scores.claim', $progress['creditAward']) }}">@csrf<button type="submit" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-300">{{ __('Claim credit') }}</button></form>
                @elseif ($isComplete)
                    <form method="POST" action="{{ route('learning.credit-scores.course-claim', $enrollment) }}">@csrf<button type="submit" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-300">{{ __('Claim credit') }}</button></form>
                @else
                    <span class="text-gray-600 dark:text-gray-400">{{ __('Earn after course completion') }}</span>
                @endif
            </div>
        @endif

        <div class="mt-4">
            @if ($isComplete)
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l3.586-3.586Z" clip-rule="evenodd"/></svg>{{ __('Completed') }}</span>
                    <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center rounded-lg border border-brand-500 px-4 py-2 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:text-brand-300 dark:hover:bg-brand-500/10">{{ __('View Course') }}</a>
                </div>
            @elseif ($percentage > 0 || $enrollment->started_at)
                <a href="{{ $actionUrl }}" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">{{ __('Continue Learning') }}</a>
            @else
                <a href="{{ $actionUrl }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-500 px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:text-brand-300 dark:hover:bg-brand-500/10">{{ __('Start Course') }}</a>
            @endif
        </div>
    </div>
</article>
