@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="My Trainees" />
<x-common.component-card title="Owned-course progress" desc="Accepted trainees are grouped by course and ordered by enrollment date.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_260px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search trainee" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <x-form.select name="course_id" :options="$courses->pluck('title', 'id')" :value="request('course_id')" placeholder="All my courses" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
    </form>

    @php($groupedEnrollments = $enrollments->getCollection()->groupBy('course_id'))
    <div class="space-y-3">
        @forelse ($groupedEnrollments as $courseId => $courseEnrollments)
            @php($course = $courseEnrollments->first()->course)
            <section x-data="{ expanded: false }" class="overflow-hidden rounded-2xl border border-gray-200 transition-all duration-200 hover:border-brand-200 hover:shadow-theme-sm dark:border-gray-800 dark:hover:border-brand-500/40">
                <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-controls="trainee-course-{{ $courseId }}" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-brand-50/60 dark:hover:bg-brand-500/5">
                    <span class="min-w-0"><span class="block truncate text-base font-semibold text-gray-800 dark:text-white">{{ $course->title }}</span><span class="mt-1 block text-xs text-gray-500">{{ $courseEnrollments->count() }} accepted trainee{{ $courseEnrollments->count() === 1 ? '' : 's' }}</span></span>
                    <span class="flex shrink-0 items-center gap-2"><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Accepted {{ $courseEnrollments->count() }}</span><span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors dark:border-gray-700 dark:text-gray-300"><i class="bi text-sm" :class="expanded ? 'bi-dash' : 'bi-plus'" aria-hidden="true"></i></span></span>
                </button>

                <div id="trainee-course-{{ $courseId }}" x-show="expanded" x-collapse.duration.300ms x-cloak class="border-t border-gray-100 dark:border-gray-800">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($courseEnrollments as $enrollment)
                            <div class="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-white/[0.03]">
                                <div class="min-w-0"><p class="font-medium text-gray-800 transition-colors group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">{{ $enrollment->trainee->name }}</p><p class="text-xs text-gray-500">{{ $enrollment->trainee->email }}</p></div>
                                <div class="flex flex-wrap items-center gap-4 sm:justify-end"><span class="text-xs text-gray-500">Enrolled {{ $enrollment->enrolled_at?->format('M j, Y H:i') }}</span><div class="w-36"><div class="rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $enrollment->progress_percentage }}%"></div></div><span class="text-xs text-gray-500">{{ $enrollment->progress_percentage }}%</span></div><x-ui.badge :color="$enrollment->status->value === 'completed' ? 'success' : 'primary'">{{ ucfirst($enrollment->status->value) }}</x-ui.badge></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">No enrolled trainees found for your courses.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $enrollments->links() }}</div>
</x-common.component-card>
@endsection
