@extends('layouts.trainee.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Test Catalog" />

<div class="space-y-6">
    <x-common.component-card title="Browse tests" desc="Explore published tests available for your training and apply when you are ready.">
        <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_260px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Search tests" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
            <x-form.select name="category_id" :options="$categories->pluck('name', 'id')" :value="request('category_id')" placeholder="All categories" />
            <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
        </form>
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($assessments as $assessment)
                @php($application = $assessment->applications->first())
                @php($assigned = $assessment->assignments->isNotEmpty())
                <article class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/50">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            @if ($assessment->category)<span class="text-xs font-semibold uppercase tracking-wide text-brand-500">{{ $assessment->category->name }}</span>@endif
                            <h2 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $assessment->title }}</h2>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5A2.5 2.5 0 0 1 7.5 3h9A2.5 2.5 0 0 1 19 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 18.5v-13Z"/><path stroke-linecap="round" d="M8 7h8M8 11h8M8 15h5"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 flex-1 text-sm leading-6 text-gray-500">{{ Str::limit($assessment->description ?: 'Test details are available on the test overview.', 120) }}</p>
                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500">
                        <span>{{ $assessment->questions_count }} {{ Str::plural(__('question'), $assessment->questions_count) }}</span>
                        <span>{{ $assessment->duration_minutes }} {{ __('minutes') }}</span>
                        <span>Pass {{ rtrim(rtrim(number_format((float) $assessment->passing_percentage, 2), '0'), '.') }}%</span>
                    </div>
                    @if($assigned || $assessment->attempts->isNotEmpty())
                        <a href="{{ route('learning.assessments.show', $assessment) }}" class="mt-5 inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white">Go to My Tests</a>
                    @elseif($application?->status === \App\Enums\AssessmentApplicationStatus::Pending)
                        <span class="mt-5 inline-flex h-11 items-center justify-center rounded-lg border border-warning-300 bg-warning-50 px-4 text-sm font-semibold text-warning-700 dark:bg-warning-500/10">Application pending</span>
                    @else
                        <a href="{{ route('learning.assessments.catalog.show', $assessment) }}" class="mt-5 inline-flex h-11 items-center justify-center rounded-lg border border-brand-500 px-4 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 dark:text-brand-300 dark:hover:bg-brand-500/10">{{ in_array($application?->status, [\App\Enums\AssessmentApplicationStatus::Rejected, \App\Enums\AssessmentApplicationStatus::Cancelled], true) ? 'Apply again' : 'View and apply' }}</a>
                    @endif
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700"><h2 class="font-semibold text-gray-800 dark:text-white">No published tests found</h2><p class="mt-1 text-sm text-gray-500">Tests will appear here when they are published and available to your training.</p></div>
            @endforelse
        </div>
        <div class="mt-6">{{ $assessments->links() }}</div>
    </x-common.component-card>

    <x-common.component-card title="Explore test categories" desc="Browse the test catalog by subject area.">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($availableCategories as $category)
                @php($categoryColors = [['bg' => 'bg-blue-light-50 dark:bg-blue-light-500/10', 'text' => 'text-blue-light-600'], ['bg' => 'bg-cyan-50 dark:bg-cyan-500/10', 'text' => 'text-cyan-600'], ['bg' => 'bg-theme-purple-500/10', 'text' => 'text-theme-purple-500'], ['bg' => 'bg-warning-50 dark:bg-warning-500/10', 'text' => 'text-warning-600']])
                @php($categoryColor = $categoryColors[$loop->index % count($categoryColors)])
                    <a href="{{ route('learning.assessments.catalog', ['category_id' => $category->id]) }}" class="flex min-h-28 items-center gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:shadow-theme-xs dark:border-gray-800 dark:hover:border-brand-500/50">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full {{ $categoryColor['bg'] }} {{ $categoryColor['text'] }}"><svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"/><path stroke-linecap="round" d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                    <span class="min-w-0 flex-1"><strong class="block text-sm font-semibold leading-5 text-gray-800 dark:text-white">{{ $category->name }}</strong><span class="mt-1 block text-sm text-gray-500">{{ $category->assessments_count }} {{ Str::plural(__('Test'), $category->assessments_count) }}</span></span><span class="text-2xl text-gray-400" aria-hidden="true">›</span>
                </a>
            @empty
                <p class="col-span-full text-sm text-gray-500">Test categories will appear as published tests are organized.</p>
            @endforelse
        </div>
    </x-common.component-card>
</div>
@endsection
