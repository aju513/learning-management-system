@extends('layouts.app')

@section('content')
@php($application = $course->enrollments->first())
<x-common.page-breadcrumb :pageTitle="$course->title" />
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <x-common.component-card :title="$course->title" :desc="$course->short_description">
            @if ($course->thumbnail_path)<img src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" class="mb-5 max-h-80 w-full rounded-xl object-cover">@endif
            <div class="prose max-w-none text-sm text-gray-600 dark:text-gray-300">{{ $course->description ?: 'No extended description has been added yet.' }}</div>
        </x-common.component-card>
        <x-common.component-card title="Curriculum preview" desc="Module and learning-item titles are visible before enrollment; content unlocks after approval.">
            <div class="space-y-4">
                @forelse ($course->modules as $module)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ $loop->iteration }}. {{ $module->title }}</h3>
                        <ul class="mt-2 space-y-1 text-sm text-gray-500">@foreach ($module->materials as $material)<li>{{ $material->title }} · {{ str($material->type->value)->replace('_', ' ')->title() }}</li>@endforeach</ul>
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
                    <a href="{{ route('learning.courses.index') }}" class="block rounded-lg bg-success-500 px-4 py-3 text-center text-sm font-medium text-white">Open My Learning</a>
                @endif
            </div>
        </x-common.component-card>
    </aside>
</div>
@endsection
