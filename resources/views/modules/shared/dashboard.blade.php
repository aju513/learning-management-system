<x-common.page-breadcrumb :pageTitle="$title" />
<div class="mb-6 rounded-2xl bg-gradient-to-r from-brand-500 to-brand-700 p-6 text-white">
    <p class="text-sm text-white/75">{{ $context }}</p>
    <h1 class="mt-1 text-2xl font-bold">Welcome back, {{ auth()->user()->name }}</h1>
</div>
@if(isset($creditAlerts) && $creditAlerts['eligibleCount'] > 0)
    <a href="{{ route('learning.credit-scores.index') }}" class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
        <span><span class="block font-semibold">You haven’t claimed all your credit scores.</span><span class="mt-1 block text-sm">{{ number_format($creditAlerts['eligibleTotal'], 2) }} credits are ready to claim for {{ $creditAlerts['fiscalYear']?->name }}.</span></span>
        <span class="rounded-lg bg-warning-500 px-4 py-2 text-sm font-medium text-white">Review credits</span>
    </a>
@endif
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($metrics as $label => $value)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $value }}</p>
        </div>
    @endforeach
</div>
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <x-common.component-card title="Recent courses">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($courses as $course)
                <div class="py-3">
                    <p class="font-medium text-gray-800 dark:text-white">{{ $course->title }}</p>
                    <p class="text-xs text-gray-500">{{ $course->status->value }} @if(isset($course->enrollments_count)) · {{ $course->enrollments_count }} enrollment records @elseif($course->relationLoaded('enrollments')) · {{ $course->enrollments->first()?->progress_percentage ?? 0 }}% complete @endif</p>
                </div>
            @empty
                <p class="py-6 text-sm text-gray-500">No courses yet.</p>
            @endforelse
        </div>
    </x-common.component-card>
    <x-common.component-card title="Recent results">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($results as $result)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white">{{ $result->assessment->title }}</p>
                        <p class="text-xs text-gray-500">{{ $result->trainee?->name ?? auth()->user()->name }} · Attempt {{ $result->attempt_number }}</p>
                    </div>
                    <x-ui.badge :color="$result->passed ? 'success' : 'error'">{{ $result->score_percentage }}%</x-ui.badge>
                </div>
            @empty
                <p class="py-6 text-sm text-gray-500">No graded attempts yet.</p>
            @endforelse
        </div>
    </x-common.component-card>
</div>
