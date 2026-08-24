@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="My Trainees" />
<x-common.component-card title="Trainee progress and quizzes" desc="Review course progress, assigned quizzes, due dates, attempts, and scores in one place.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_260px_auto]">
        <x-form.input name="search" label="Search trainee" :value="request('search')" />
        <x-form.select name="course_id" :options="$courses->pluck('title', 'id')" :value="request('course_id')" placeholder="All my courses" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">{{ __('Filter') }}</button>
    </form>

    @php($groupedEnrollments = $enrollments->getCollection()->groupBy('course_id'))
    <div class="space-y-3">
        @forelse ($groupedEnrollments as $courseId => $courseEnrollments)
            @php($course = $courseEnrollments->first()->course)
            <section x-data="{ expanded: false }" class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-controls="trainee-course-{{ $courseId }}" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-brand-50/60 dark:hover:bg-brand-500/5">
                    <span class="min-w-0"><span class="block truncate text-base font-semibold text-gray-800 dark:text-white">{{ $course->title }}</span><span class="mt-1 block text-xs text-gray-500">{{ $courseEnrollments->count() }} {{ __('accepted trainees') }}</span></span>
                    <span class="flex shrink-0 items-center gap-2"><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ __('Accepted') }} {{ $courseEnrollments->count() }}</span><span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-300"><i class="bi text-sm" :class="expanded ? 'bi-dash' : 'bi-plus'" aria-hidden="true"></i></span></span>
                </button>
                <div id="trainee-course-{{ $courseId }}" x-show="expanded" x-collapse.duration.300ms x-cloak class="border-t border-gray-100 dark:border-gray-800">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($courseEnrollments as $enrollment)
                            @php($assignments = $enrollment->trainee->assessmentAssignments ?? collect())
                            <article class="px-5 py-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"><div class="min-w-0"><p class="font-medium text-gray-800 dark:text-white">{{ $enrollment->trainee->name }}</p><p class="text-xs text-gray-500">{{ $enrollment->trainee->email }} · {{ __('Enrolled') }} {{ $enrollment->enrolled_at?->format('M j, Y H:i') }}</p></div><div class="w-full max-w-xs"><div class="flex justify-between text-xs text-gray-500"><span>{{ __('Course progress') }}</span><span>{{ $enrollment->progress_percentage }}%</span></div><div class="mt-1 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $enrollment->progress_percentage }}%"></div></div><x-ui.badge :color="$enrollment->status->value === 'completed' ? 'success' : 'primary'">{{ ucfirst($enrollment->status->value) }}</x-ui.badge></div></div>
                                <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">{{ __('Assigned quiz') }}</th><th class="px-3 py-2">{{ __('Due date') }}</th><th class="px-3 py-2">{{ __('Attempts') }}</th><th class="px-3 py-2">{{ __('Score') }}</th><th class="px-3 py-2">{{ __('Completion') }}</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@forelse($assignments as $assignment) @php($latestAttempt = $enrollment->trainee->assessmentAttempts?->where('assessment_id', $assignment->assessment_id)->sortByDesc('attempt_number')->first())<tr><td class="px-3 py-3 font-medium text-gray-800 dark:text-white">{{ $assignment->assessment->title }}</td><td class="px-3 py-3 text-gray-500">{{ $assignment->due_at?->format('M j, Y H:i') ?? __('No deadline') }}</td><td class="px-3 py-3 text-gray-500">{{ $enrollment->trainee->assessmentAttempts?->where('assessment_id', $assignment->assessment_id)->count() ?? 0 }}</td><td class="px-3 py-3 text-gray-500">{{ $latestAttempt?->score_percentage !== null ? $latestAttempt->score_percentage.'%' : __('Not graded') }}</td><td class="px-3 py-3"><x-ui.badge :color="$latestAttempt?->passed ? 'success' : 'warning'">{{ $latestAttempt?->passed ? __('Passed') : ($latestAttempt ? __('In progress') : __('Not started')) }}</x-ui.badge></td></tr>@empty<tr><td colspan="5" class="px-3 py-4 text-center text-xs text-gray-500">{{ __('No assigned quizzes for this trainee.') }}</td></tr>@endforelse</tbody></table></div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">{{ __('No enrolled trainees found for your courses.') }}</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $enrollments->links() }}</div>
</x-common.component-card>
@endsection
