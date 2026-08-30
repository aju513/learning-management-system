@props([
    'assessment',
    'meta',
])

@php
    $assignment = $assessment->assignments->first();
    $status = $meta['status'];
    $statusColor = match ($status) {
        'completed' => 'success',
        'failed' => 'error',
        'pending' => 'warning',
        default => 'light',
    };
    $latestAttempt = $meta['latestAttempt'];
    $actionIsResult = $meta['action'] === 'result' && $latestAttempt;
    $actionLabel = __($meta['actionLabel']);
@endphp

<article class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-start justify-between gap-4">
        <h2 class="min-w-0 text-lg font-semibold leading-6 text-gray-900 dark:text-white">{{ $assessment->title }}</h2>
        <x-ui.badge :color="$statusColor" size="sm">{{ __($meta['statusLabel']) }}</x-ui.badge>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
        <span class="inline-flex items-center gap-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5A2.5 2.5 0 0 1 7.5 3h9A2.5 2.5 0 0 1 19 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 18.5v-13Z"/><path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h5"/></svg>
            {{ $assessment->questions_count }} {{ Str::plural(__('question'), $assessment->questions_count) }}
        </span>
        <span class="inline-flex items-center gap-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
            {{ $assessment->duration_minutes }} {{ __('minutes') }}
        </span>
    </div>

    @if ($meta['score'] !== null)
        <div class="mt-3 text-right">
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Score') }}</span>
            <strong class="ml-2 text-xl {{ $status === 'completed' ? 'text-success-600 dark:text-success-400' : 'text-error-600 dark:text-error-400' }}">{{ rtrim(rtrim(number_format((float) $meta['score'], 2), '0'), '.') }}%</strong>
        </div>
    @endif

    <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-800">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
            @if ($meta['completedAt'])
                <span>{{ __('Completed on :date', ['date' => $meta['completedAt']->format('M j, Y')]) }}</span>
            @elseif ($assignment?->due_at)
                <span>{{ __('Due :date', ['date' => $assignment->due_at->format('M j, Y')]) }}</span>
            @endif
            @if ($meta['completedAt'] || (! $meta['completedAt'] && $assignment?->due_at))
                <span class="text-gray-300 dark:text-gray-700" aria-hidden="true">|</span>
            @endif
            <span>{{ __('Attempts: :used/:total', ['used' => $assessment->attempts_count, 'total' => $assessment->max_attempts]) }}</span>
        </div>

        <div class="mt-4">
            @if ($actionIsResult)
                <a href="{{ route('learning.assessments.attempts.show', $latestAttempt) }}" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-brand-500 px-4 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:text-brand-300 dark:hover:bg-brand-500/10">{{ $actionLabel }}</a>
            @else
                <form method="POST" action="{{ route('learning.assessments.start', $assessment) }}">
                    @csrf
                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-brand-500 bg-white px-4 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-transparent dark:text-brand-300 dark:hover:bg-brand-500/10" @disabled(! $meta['canStart'])>{{ $actionLabel }}</button>
                </form>
            @endif
        </div>
    </div>
</article>
