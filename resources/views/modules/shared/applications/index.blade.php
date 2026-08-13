@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Course Applications" />
<x-common.component-card title="Review applications" desc="Approve applications to unlock course learning, or reject them with an optional explanation.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_240px_180px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search trainee" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <x-form.select name="course_id" :options="$courses->pluck('title', 'id')" :value="request('course_id')" placeholder="All courses" />
        <x-form.select name="status" :options="['pending' => 'Pending', 'rejected' => 'Rejected']" :value="request('status')" placeholder="All statuses" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
    </form>

    @php($groupedApplications = $applications->getCollection()->groupBy('course_id'))
    <div class="space-y-3">
        @forelse ($groupedApplications as $courseId => $courseApplications)
                @php($course = $courses->firstWhere('id', $courseId) ?? $courseApplications->first()->course)
            <section x-data="{ expanded: false }" class="overflow-hidden rounded-2xl border border-gray-200 transition-all duration-200 hover:border-brand-200 hover:shadow-theme-sm dark:border-gray-800 dark:hover:border-brand-500/40">
                <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-controls="application-course-{{ $courseId }}" class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-brand-50/60 dark:hover:bg-brand-500/5">
                    <span class="min-w-0"><span class="block truncate text-base font-semibold text-gray-800 transition-colors group-hover:text-brand-600 dark:text-white">{{ $course->title }}</span><span class="mt-1 block text-xs text-gray-500">{{ $courseApplications->count() }} application{{ $courseApplications->count() === 1 ? '' : 's' }} in this view</span></span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span class="hidden rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-700 sm:inline-flex dark:bg-warning-500/10 dark:text-warning-400">Applied {{ $course->applied_count }}</span>
                        <span class="hidden rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 sm:inline-flex dark:bg-success-500/10 dark:text-success-400">Accepted {{ $course->accepted_count }}</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors dark:border-gray-700 dark:text-gray-300"><i class="bi text-sm" :class="expanded ? 'bi-dash' : 'bi-plus'" aria-hidden="true"></i></span>
                    </span>
                </button>

                <div id="application-course-{{ $courseId }}" x-show="expanded" x-collapse.duration.300ms x-cloak class="border-t border-gray-100 dark:border-gray-800">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($courseApplications as $application)
                            <div class="group flex flex-col gap-4 px-5 py-4 transition-colors hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-white/[0.03]">
                                <div class="min-w-0"><p class="font-medium text-gray-800 transition-colors group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">{{ $application->trainee->name }}</p><p class="text-xs text-gray-500">{{ $application->trainee->email }}</p></div>
                                <div class="flex flex-wrap items-center gap-3 sm:justify-end"><span class="text-xs text-gray-500">Applied {{ $application->requested_at?->format('M j, Y H:i') }}</span><x-ui.badge :color="$application->status->value === 'pending' ? 'warning' : 'error'">{{ ucfirst($application->status->value) }}</x-ui.badge>@if($application->review_note)<span class="max-w-xs text-xs text-gray-500">{{ $application->review_note }}</span>@endif
                                    @if ($application->status->value === 'pending')
                                        <div class="flex flex-wrap gap-2"><form method="POST" action="{{ route($routePrefix.'.applications.approve', $application) }}">@csrf @method('PATCH')<button class="rounded-lg bg-success-500 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-success-600">Approve</button></form><form method="POST" action="{{ route($routePrefix.'.applications.reject', $application) }}" class="flex gap-2">@csrf @method('PATCH')<input name="review_note" maxlength="1000" placeholder="Optional rejection reason" class="h-9 min-w-48 rounded-lg border border-gray-300 bg-transparent px-3 text-xs dark:border-gray-700 dark:text-white"><button class="rounded-lg bg-error-50 px-3 py-2 text-xs font-medium text-error-600 transition-colors hover:bg-error-100">Reject</button></form></div>
                                    @else
                                        <span class="text-xs text-gray-500">Reviewed {{ $application->reviewed_at?->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">No course applications found.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $applications->links() }}</div>
</x-common.component-card>
@endsection
