@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$title">
    <x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('courses.show'), $course) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">{{ __('Back to editor') }}</a></x-slot:actions>
</x-common.page-breadcrumb>
<div class="mx-auto max-w-5xl space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-brand-500 to-brand-700 p-8 text-white">
        <p class="text-sm text-white/75">{{ __('Trainee preview') }}</p>
        <h1 class="mt-2 text-3xl font-bold">{{ $course->title }}</h1>
        <p class="mt-3 max-w-3xl text-sm text-white/85">{{ $course->description ?: $course->short_description }}</p>
    </section>
    @foreach($course->modules as $module)
        <section class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800"><p class="text-xs font-semibold uppercase tracking-wide text-brand-500">{{ __('Module') }} {{ $loop->iteration }}</p><h2 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h2></div>
            <div class="space-y-5 p-6">
                @foreach($module->chapters as $chapter)
                    <article class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Chapter') }} {{ $loop->iteration }}</p><h3 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $chapter->title }}</h3>@if($chapter->description)<p class="mt-2 text-sm text-gray-500">{{ $chapter->description }}</p>@endif
                        <div class="mt-4 space-y-4">
                            @forelse($chapter->materials as $material)
                                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"><div class="flex flex-wrap items-center justify-between gap-2"><h4 class="font-medium text-gray-800 dark:text-white">{{ $material->title }}</h4><x-ui.badge color="light">{{ $material->type->label() }}</x-ui.badge></div>@if($material->description)<p class="mt-2 text-sm text-gray-500">{{ $material->description }}</p>@endif @if($material->type === \App\Enums\MaterialType::Article && $material->content)<div class="prose mt-3 max-w-none text-sm dark:prose-invert">{!! $material->content !!}</div>@elseif($material->type === \App\Enums\MaterialType::CourseAssessment)<p class="mt-3 text-sm text-gray-500">{{ $material->courseAssessment?->questions?->count() ?? 0 }} {{ __('questions') }} · {{ __('Passing score') }} {{ $material->courseAssessment?->passing_percentage ?? 0 }}%</p>@elseif($material->type === \App\Enums\MaterialType::File)<p class="mt-3 text-sm text-gray-500">{{ $material->original_filename ?: __('File ready for trainees') }}</p>@elseif($material->type === \App\Enums\MaterialType::Video && $material->video_url)<p class="mt-3 text-sm text-brand-600">{{ __('Video link configured') }}</p>@endif</div>
                            @empty
                                <p class="text-sm text-warning-700">{{ __('This chapter has no materials yet.') }}</p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
