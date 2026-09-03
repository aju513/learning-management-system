@extends('layouts.trainee.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$assessment->title" :translate="false" />
<div class="mx-auto grid max-w-6xl gap-6 xl:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <section class="rounded-2xl border border-brand-200 bg-brand-50 p-7 dark:border-brand-500/30 dark:bg-brand-500/10">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><p class="text-sm font-semibold uppercase tracking-wide text-brand-600">Test overview</p><h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $assessment->title }}</h1><p class="mt-3 max-w-3xl text-sm leading-7 text-gray-600 dark:text-gray-300">{{ $assessment->description ?: 'Review the test information and instructions before starting.' }}</p></div>
                <x-ui.badge :color="in_array($meta['status'], ['passed']) ? 'success' : (in_array($meta['status'], ['failed', 'rejected']) ? 'error' : 'primary')">{{ $meta['statusLabel'] }}</x-ui.badge>
            </div>
            <div class="mt-7 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div><p class="text-xs text-gray-500">Questions</p><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $assessment->questions_count }}</p></div>
                <div><p class="text-xs text-gray-500">Duration</p><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $assessment->duration_minutes }} min</p></div>
                <div><p class="text-xs text-gray-500">Passing score</p><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format((float) $assessment->passing_percentage, 0) }}%</p></div>
                <div><p class="text-xs text-gray-500">Attempts</p><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $assessment->attempts_count }}/{{ $assessment->max_attempts }}</p></div>
            </div>
            <div class="mt-7 flex flex-wrap gap-3">
                @if($meta['activeAttempt'])
                    <a href="{{ route('learning.assessments.attempts.show', $meta['activeAttempt']) }}" target="_blank" rel="noopener" class="rounded-lg bg-brand-500 px-5 py-3 text-sm font-semibold text-white">Continue test</a>
                @elseif($meta['canStart'])
                    <form method="POST" action="{{ route('learning.assessments.start', $assessment) }}" target="_blank">@csrf<button class="rounded-lg bg-brand-500 px-5 py-3 text-sm font-semibold text-white">{{ $assessment->attempts_count ? 'Retry test' : 'Start test' }}</button></form>
                @endif
                <a href="{{ route('learning.assessments.index') }}" class="rounded-lg border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-200">Back to My Tests</a>
            </div>
        </section>

        <x-common.component-card title="Instructions" desc="Read these instructions before opening the timed attempt.">
            <div class="whitespace-pre-line text-sm leading-7 text-gray-600 dark:text-gray-300">{{ $assessment->instructions ?: 'Read every question carefully. Your answers save automatically. Submit before the timer reaches zero.' }}</div>
        </x-common.component-card>

        <x-common.component-card title="Attempt history" desc="All attempts for this test are listed newest first.">
            <div class="space-y-3">
                @forelse($assessment->attempts as $attempt)
                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div><p class="font-semibold text-gray-800 dark:text-white">Attempt {{ $attempt->attempt_number }}</p><p class="mt-1 text-xs text-gray-500">{{ $attempt->submitted_at?->format('M j, Y H:i') ?? 'Started '.$attempt->started_at?->diffForHumans() }}</p></div>
                        <div class="flex items-center gap-4"><x-ui.badge :color="$attempt->status->value === 'graded' ? ($attempt->passed ? 'success' : 'error') : 'warning'">{{ $attempt->status->value === 'pending_review' ? 'Pending review' : ($attempt->status->value === 'in_progress' ? 'In progress' : ($assessment->show_results ? ($attempt->passed ? 'Passed' : 'Failed') : 'Submitted')) }}</x-ui.badge>@if($assessment->show_results && $attempt->score_percentage !== null)<span class="font-semibold text-gray-800 dark:text-white">{{ number_format((float) $attempt->score_percentage, 0) }}%</span>@endif<a href="{{ route('learning.assessments.attempts.show', $attempt) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-500">Open</a></div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700">No attempts yet.</p>
                @endforelse
            </div>
        </x-common.component-card>
    </div>
    <aside class="space-y-6">
        <x-common.component-card title="Availability">
            <dl class="space-y-4 text-sm"><div><dt class="text-gray-500">Assigned</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $meta['assignment']?->assigned_at?->format('M j, Y') ?? 'Not assigned' }}</dd></div><div><dt class="text-gray-500">Due</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $meta['assignment']?->due_at?->format('M j, Y H:i') ?? 'No due date' }}</dd></div><div><dt class="text-gray-500">Category</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->category?->name ?? 'Uncategorized' }}</dd></div></dl>
            @if($meta['application']?->review_note)<p class="mt-5 rounded-lg bg-error-50 p-3 text-sm text-error-700 dark:bg-error-500/10">{{ $meta['application']->review_note }}</p>@endif
        </x-common.component-card>
        @if($creditAward)
            <x-common.component-card title="Test credit score">
                <p class="text-2xl font-bold text-brand-600">+{{ number_format((float) $creditAward->points, 2) }} credits</p>
                @if($creditAward->isClaimed())<p class="mt-2 text-sm text-success-700">Credit claimed.</p>@else<form method="POST" action="{{ route('learning.credit-scores.claim', $creditAward) }}" class="mt-4">@csrf<button class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">Claim credits</button></form>@endif
            </x-common.component-card>
        @endif
    </aside>
</div>
@endsection
