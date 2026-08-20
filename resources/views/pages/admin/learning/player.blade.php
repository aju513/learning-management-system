@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$enrollment->course->title" />
<div class="grid gap-6 xl:grid-cols-[320px_1fr]">
    <aside class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-5">
            <div class="mb-1 flex justify-between text-xs text-gray-500"><span>Course progress</span><span>{{ $enrollment->progress_percentage }}%</span></div>
            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $enrollment->progress_percentage }}%"></div></div>
        </div>
        <nav class="space-y-5">
            @foreach($enrollment->course->modules as $module)
                <section>
                    <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">{{ $loop->iteration }}. {{ $module->title }}</h3>
                    <div class="space-y-3">
                        @foreach($module->chapters as $chapter)
                            <div>
                                <h4 class="mb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $chapter->title }}</h4>
                                <div class="space-y-1">
                                    @foreach($chapter->materials as $item)
                                        @php($done = $enrollment->materialProgress->firstWhere('learning_material_id', $item->id)?->completed_at)
                                        <a href="{{ route('learning.courses.materials.show', [$enrollment, $item]) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm {{ $item->id === $material->id ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.03]' }}">
                                            <span class="{{ $done ? 'text-success-500' : 'text-gray-400' }}">{{ $done ? '✓' : '○' }}</span>
                                            <span>{{ $item->title }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </nav>
    </aside>

<main class="space-y-5">
        @if(session('credit_award_id'))
            <div x-data="{ open: true }" x-show="open" x-cloak class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
                <div class="flex items-start justify-between gap-4"><div><p class="font-semibold text-brand-800 dark:text-brand-200">Claim this credit score</p><p class="mt-1 text-sm text-brand-700 dark:text-brand-300">You completed this course and have a credit score ready to claim.</p></div><button type="button" @click="open = false" class="text-brand-600" aria-label="Close">&times;</button></div>
                <form method="POST" action="{{ route('learning.credit-scores.claim', session('credit_award_id')) }}" class="mt-4">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Claim credit score</button></form>
            </div>
        @endif
        <x-common.component-card :title="$material->title" :desc="$material->description ?? ''">
            @switch($material->type)
                @case(\App\Enums\MaterialType::Article)
                    <div class="prose max-w-none text-gray-700 dark:text-gray-300">{!! $material->content !!}</div>
                    @break
                @case(\App\Enums\MaterialType::Video)
                    @if($material->content)<div class="prose mb-5 max-w-none text-gray-700 dark:text-gray-300">{!! $material->content !!}</div>@endif
                    @if($material->video_url)<a href="{{ $material->video_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Open video</a>@endif
                    @if($material->file_path)<a href="{{ route('learning.courses.materials.download', [$enrollment, $material]) }}" class="ml-2 inline-flex rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Download video</a>@endif
                    @break
                @case(\App\Enums\MaterialType::File)
                    @if($material->content)<div class="prose mb-5 max-w-none text-gray-700 dark:text-gray-300">{!! $material->content !!}</div>@endif
                    @if($material->file_path)
                        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                            <p class="font-medium text-gray-800 dark:text-white">{{ $material->original_filename }}</p>
                            <p class="mt-1 text-sm uppercase text-gray-500">{{ $material->file_type === 'legacy' ? 'File' : $material->file_type }}</p>
                            <a href="{{ route('learning.courses.materials.download', [$enrollment, $material]) }}" class="mt-4 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Download file</a>
                        </div>
                    @else
                        <p class="text-sm text-error-500">The learning file is unavailable.</p>
                    @endif
                    @break
                @case(\App\Enums\MaterialType::Link)
                    @if($material->content)<div class="prose mb-5 max-w-none text-gray-700 dark:text-gray-300">{!! $material->content !!}</div>@endif
                    <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Open external resource</a>
                    @break
                @case(\App\Enums\MaterialType::CourseAssessment)
                    @if($material->content)<div class="prose mb-5 max-w-none text-gray-700 dark:text-gray-300">{!! $material->content !!}</div>@endif
                    <div class="rounded-xl border border-brand-200 bg-brand-50 p-6 dark:border-brand-500/30 dark:bg-brand-500/10">
                        <p class="font-semibold text-gray-800 dark:text-white">Course assessment</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Pass with {{ $material->courseAssessment?->passing_percentage ?? 60 }}% or higher. You can retake it until you pass.</p>
                        @if(! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at && $material->courseAssessment)
                            <form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$enrollment, $material]) }}" class="mt-4">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Start assessment</button></form>
                        @endif
                    </div>
                    @break
                @default
                    <p class="text-sm text-gray-500">This learning material is unavailable.</p>
            @endswitch
        </x-common.component-card>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>@if($previous)<a href="{{ route('learning.courses.materials.show', [$enrollment, $previous]) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">← Previous</a>@endif</div>
            <div class="flex gap-3">
                @if(! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at)
                    @if($material->type !== \App\Enums\MaterialType::CourseAssessment)
                        <form method="POST" action="{{ route('learning.courses.materials.complete', [$enrollment, $material]) }}">@csrf <button class="rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white">Mark complete</button></form>
                    @else
                        <span class="text-sm text-gray-500">Pass the assessment to complete this material.</span>
                    @endif
                @else
                    <x-ui.badge color="success">Completed</x-ui.badge>
                @endif
                @if($next)<a href="{{ route('learning.courses.materials.show', [$enrollment, $next]) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Next →</a>@endif
            </div>
        </div>
    </main>
</div>
@endsection
