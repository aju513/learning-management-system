@extends('layouts.app')

@section('content')
@php($application = $course->enrollments->firstWhere('course_id', $course->id))
<x-common.page-breadcrumb :pageTitle="$course->title" :translate="false" />
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <x-common.component-card :title="$course->title" :desc="$course->short_description">
            @if ($course->thumbnail_path)<img src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" class="mb-5 max-h-80 w-full rounded-xl object-cover">@endif
            <div class="mb-5 flex flex-wrap gap-2"><x-ui.badge color="light">{{ ucfirst($course->difficulty) }}</x-ui.badge><x-ui.badge color="primary">{{ $course->estimated_duration_minutes }} minutes</x-ui.badge><x-ui.badge color="light">{{ $course->modules->count() }} modules</x-ui.badge><x-ui.badge color="light">{{ $course->modules->flatMap->chapters->flatMap->materials->count() }} course items</x-ui.badge></div>
            <div class="prose max-w-none text-sm leading-7 text-gray-600 dark:text-gray-300">{{ $course->description ?: 'No extended description has been added yet.' }}</div>
        </x-common.component-card>
        <x-common.component-card title="Curriculum preview" desc="Module and learning-item titles are visible before enrollment; content unlocks after approval.">
            <div class="space-y-4">
                @forelse ($course->modules as $module)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ $loop->iteration }}. {{ $module->title }}</h3>
                        <ul class="mt-2 space-y-1 text-sm text-gray-500">@foreach ($module->chapters->flatMap->materials as $material)<li>{{ $material->title }} · {{ $material->courseAssessment ? __('Course assessment') : $material->type->label() }}</li>@endforeach</ul>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Curriculum details are being prepared.</p>
                @endforelse
            </div>
        </x-common.component-card>
    </div>
    <aside>
        <x-common.component-card title="Course information">
            <dl class="space-y-4 text-sm">
                <div><dt class="text-gray-500">Instructor</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $course->instructor?->name ?? 'To be assigned' }}</dd></div>
                <div><dt class="text-gray-500">Category</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $course->category?->name ?? 'General' }}</dd></div>
                <div><dt class="text-gray-500">Difficulty</dt><dd class="font-medium text-gray-800 dark:text-white">{{ ucfirst($course->difficulty) }}</dd></div>
                <div><dt class="text-gray-500">Duration</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $course->estimated_duration_minutes }} minutes</dd></div>
            </dl>
            <div class="mt-6">
                @if (! $application || in_array($application->status->value, ['rejected', 'cancelled'], true))
                    <form method="POST" action="{{ route('learning.applications.store', $course) }}">@csrf<button class="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white">Apply for this course</button></form>
                @elseif ($application->status->value === 'pending')
                    <div class="rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10">Your application is pending review.</div>
                @else
                    @if($progress)
                        <div class="mb-4 rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10"><div class="flex items-center justify-between gap-3"><p class="text-sm font-semibold text-gray-800 dark:text-white">Your progress</p><p class="text-lg font-bold text-brand-600 dark:text-brand-400">{{ $progress['percentage'] }}%</p></div><div class="mt-3 h-2.5 overflow-hidden rounded-full bg-brand-100 dark:bg-brand-500/20"><div class="h-full rounded-full bg-brand-500" style="width: {{ $progress['percentage'] }}%"></div></div><div class="mt-3 grid grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300"><span>{{ $progress['completedLessons'] }} of {{ $progress['totalLessons'] }} lessons completed</span><span>Assessment: {{ $progress['assessmentStatus'] ?? 'Not included' }}</span></div></div>
                        @if($progress['assessment'] && ! $progress['assessmentPassed'] && $progress['assessmentStatus'] === 'Available')
                            <form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$application, $progress['assessmentMaterial']]) }}">@csrf<button class="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white">Take assessment</button></form>
                        @else
                            <a href="{{ route('learning.courses.player', $application) }}" class="block rounded-lg bg-brand-500 px-4 py-3 text-center text-sm font-medium text-white">{{ $progress['isComplete'] ? 'Review course' : ($application->started_at ? 'Continue course' : 'Start course') }}</a>
                        @endif
                    @endif
                @endif
            </div>
        </x-common.component-card>
    </aside>
</div>
@endsection
