@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="My Trainees" />
<x-common.component-card title="Owned-course progress" desc="Only trainees actively enrolled in courses you own are shown.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_260px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search trainee" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <x-form.select name="course_id" :options="$courses->pluck('title', 'id')" :value="request('course_id')" placeholder="All my courses" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
    </form>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-4 py-3">Trainee</th><th class="px-4 py-3">Course</th><th class="px-4 py-3">Progress</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($enrollments as $enrollment)<tr><td class="px-4 py-4"><p class="font-medium text-gray-800 dark:text-white">{{ $enrollment->trainee->name }}</p><p class="text-xs text-gray-500">{{ $enrollment->trainee->email }}</p></td><td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $enrollment->course->title }}</td><td class="px-4 py-4"><div class="w-36 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $enrollment->progress_percentage }}%"></div></div><span class="text-xs text-gray-500">{{ $enrollment->progress_percentage }}%</span></td><td class="px-4 py-4"><x-ui.badge :color="$enrollment->status->value === 'completed' ? 'success' : 'primary'">{{ ucfirst($enrollment->status->value) }}</x-ui.badge></td></tr>
        @empty<tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">No enrolled trainees found for your courses.</td></tr>@endforelse
    </tbody></table></div><div class="mt-6">{{ $enrollments->links() }}</div>
</x-common.component-card>
@endsection
