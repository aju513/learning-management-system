@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Course Catalog" />
<x-common.component-card title="Discover courses" desc="Browse published courses and apply for the training you need.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_240px_200px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search courses" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <x-form.select name="category_id" :options="$categories->pluck('name', 'id')" :value="request('category_id')" placeholder="All categories" />
        <x-form.select name="difficulty" :options="['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced']" :value="request('difficulty')" placeholder="All levels" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
    </form>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($courses as $course)
            @php($application = $course->enrollments->firstWhere('course_id', $course->id))
            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                @if ($course->thumbnail_path)
                    <img src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" class="h-40 w-full object-cover">
                @else
                    <div class="flex h-40 items-center justify-center bg-brand-50 text-4xl font-bold text-brand-500 dark:bg-brand-500/10">{{ Str::upper(Str::substr($course->title, 0, 1)) }}</div>
                @endif
                <div class="space-y-4 p-5">
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge color="light">{{ ucfirst($course->difficulty) }}</x-ui.badge>
                        @if ($course->category)<x-ui.badge color="primary">{{ $course->category->name }}</x-ui.badge>@endif
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ $course->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ Str::limit($course->short_description, 110) }}</p>
                        <p class="mt-2 text-xs text-gray-400">{{ $course->instructor?->name ?? 'Instructor pending' }} · {{ $course->modules_count }} modules · {{ $course->estimated_duration_minutes }} min</p>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('learning.catalog.show', $course) }}" class="text-sm font-medium text-brand-500">View details</a>
                        @if (! $application || in_array($application->status->value, ['rejected', 'cancelled'], true))
                            <form method="POST" action="{{ route('learning.applications.store', $course) }}">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white">Apply</button></form>
                        @elseif ($application->status->value === 'pending')
                            <x-ui.badge color="warning">Pending review</x-ui.badge>
                        @else
                            <x-ui.badge color="success">Enrolled</x-ui.badge>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700"><h2 class="font-semibold text-gray-800 dark:text-white">No published courses found</h2></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $courses->links() }}</div>
</x-common.component-card>
@endsection
