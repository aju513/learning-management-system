@extends(request()->routeIs('learning.*') ? 'layouts.trainee.app' : 'layouts.app')

@section('content')
    @php
        $filters = $filters ?? ['search' => '', 'status' => 'all', 'sort' => 'recent'];
        $status = $filters['status'] ?? 'all';
        $sort = $filters['sort'] ?? 'recent';
        $search = $filters['search'] ?? '';
        $showFilters = $showFilters ?? false;
        $filterUrl = fn (string $value): string => route('learning.assessments.index', array_filter([
            'search' => $search,
            'status' => $value === 'all' ? null : $value,
            'sort' => $sort === 'recent' ? null : $sort,
        ], fn ($value): bool => $value !== null && $value !== ''));
    @endphp

    <div class="mb-8">
        <nav aria-label="{{ __('Breadcrumb') }}">
            <ol class="flex items-center gap-2 text-sm">
                <li><a href="{{ route('learning.dashboard') }}" class="text-brand-500 hover:text-brand-600">{{ __('Home') }}</a></li>
                <li class="text-gray-400" aria-hidden="true">/</li>
                <li class="text-gray-500 dark:text-gray-400">{{ __($title) }}</li>
            </ol>
        </nav>
        <h1 class="mt-4 text-3xl font-semibold text-gray-900 dark:text-white">{{ __($title) }}</h1>
        @if ($legacyTitle ?? null)<span class="sr-only">{{ __($legacyTitle) }}</span>@endif
        <p class="mt-1 text-base text-gray-600 dark:text-gray-400">{{ __($description) }}</p>
    </div>

    @if ($showFilters)
        <section class="mb-7 pt-1">
            <form method="GET" action="{{ route('learning.assessments.index') }}" class="space-y-4">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                    <label class="relative block min-w-0 flex-1">
                        <span class="sr-only">{{ __('Search tests') }}</span>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7.5"/><path stroke-linecap="round" d="m16.5 16.5 4 4"/></svg>
                        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search tests') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-white pl-12 pr-4 text-sm text-gray-800 shadow-theme-xs outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-white/[0.03] dark:text-white">
                    </label>
                    <div class="flex flex-wrap gap-3">
                        @foreach (['all' => 'All Tests', 'pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed'] as $value => $label)
                            <a href="{{ $filterUrl($value) }}" class="inline-flex h-11 items-center justify-center rounded-lg border px-5 text-sm font-medium transition {{ $status === $value ? 'border-brand-500 bg-brand-500 text-white shadow-theme-xs' : 'border-gray-300 bg-white text-gray-800 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-200 dark:hover:border-brand-500' }}" @if ($status === $value) aria-current="page" @endif>{{ __($label) }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-base text-gray-800 dark:text-gray-200">{{ __('Showing :count tests', ['count' => $assessments->count()]) }}</p>
                    <label class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <span class="sr-only">{{ __('Sort tests') }}</span>
                        <select name="sort" onchange="this.form.submit()" class="h-11 min-w-48 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-white/[0.03] dark:text-white">
                            <option value="recent" @selected($sort === 'recent')>{{ __('Recently Added') }}</option>
                            <option value="title" @selected($sort === 'title')>{{ __('Test Name') }}</option>
                        </select>
                    </label>
                </div>
            </form>
        </section>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        @forelse ($assessments as $assessment)
            <x-trainee::assessment-card :assessment="$assessment" :meta="$assessmentMeta[$assessment->id]" />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center dark:border-gray-700 dark:bg-white/[0.03]">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ $showFilters && ($search || $status !== 'all') ? __('No tests match your filters') : __($emptyTitle) }}</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">{{ $showFilters && ($search || $status !== 'all') ? __('Try a different search or status filter.') : __($emptyDescription) }}</p>
                @if ($showFilters && ($search || $status !== 'all'))
                    <a href="{{ route('learning.assessments.index') }}" class="mt-6 inline-flex rounded-lg border border-brand-500 px-4 py-2.5 text-sm font-semibold text-brand-600 dark:text-brand-300">{{ __('Clear filters') }}</a>
                @endif
            </div>
        @endforelse
    </div>
@endsection
