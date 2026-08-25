@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$title" />
<p class="mb-6 text-sm text-gray-500">{{ $description }}</p>
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($assessments as $assessment)
        @php($assignment = $assessment->assignments->first())
        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="bg-gradient-to-r from-brand-50 to-cyan-50 p-6 dark:from-brand-500/10 dark:to-cyan-500/10">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-brand-500">Standalone test</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $assessment->title }}</h2>
                    </div>
                    <x-ui.badge color="{{ $assessment->attempts_count > 0 ? 'primary' : 'warning' }}">{{ $assessment->attempts_count > 0 ? 'In progress' : 'Not started' }}</x-ui.badge>
                </div>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ Str::limit($assessment->description, 120) }}</p>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Questions</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $assessment->questions_count }}</dd></div>
                    <div><dt class="text-gray-500">Duration</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $assessment->duration_minutes }} min</dd></div>
                    <div><dt class="text-gray-500">Pass score</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $assessment->passing_percentage }}%</dd></div>
                    <div><dt class="text-gray-500">Attempts</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $assessment->attempts_count }}/{{ $assessment->max_attempts }}</dd></div>
                </dl>
                @if($assignment?->due_at)
                    <p class="mt-4 rounded-lg bg-warning-50 px-3 py-2 text-xs text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">Due {{ $assignment->due_at->format('M j, Y g:i A') }}</p>
                @endif
                <form method="POST" action="{{ route('learning.assessments.start', $assessment) }}" class="mt-5">@csrf <button class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" @disabled($assessment->attempts_count >= $assessment->max_attempts)>{{ $assessment->attempts_count > 0 ? 'Continue test' : 'Start test' }}</button></form>
            </div>
        </article>
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700"><h2 class="font-semibold text-gray-800 dark:text-white">{{ $emptyTitle }}</h2><p class="mt-1 text-sm text-gray-500">{{ $emptyDescription }}</p></div>
    @endforelse
</div>
@endsection
