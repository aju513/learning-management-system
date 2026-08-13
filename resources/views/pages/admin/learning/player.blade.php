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
        <x-common.component-card :title="$material->title" :desc="$material->description ?? ''">
            @switch($material->type)
                @case(\App\Enums\MaterialType::Article)
                    <div class="prose max-w-none text-gray-700 dark:text-gray-300">{!! $material->content !!}</div>
                    @break
                @case(\App\Enums\MaterialType::Video)
                    @if($material->external_url)<a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Open video</a>@endif
                    @if($material->file_path)<a href="{{ route('learning.courses.materials.download', [$enrollment, $material]) }}" class="ml-2 inline-flex rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Download video</a>@endif
                    @break
                @case(\App\Enums\MaterialType::ExternalLink)
                    <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Open external resource</a>
                    @break
                @case(\App\Enums\MaterialType::Assessment)
                    @if($material->assessment)
                        <div class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]">
                            <h3 class="font-semibold text-gray-800 dark:text-white">{{ $material->assessment->title }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Pass score {{ $material->assessment->passing_percentage }}% · {{ $material->assessment->duration_minutes }} minutes</p>
                            <form method="POST" action="{{ route('learning.assessments.start', $material->assessment) }}" class="mt-4">@csrf <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Start assessment</button></form>
                        </div>
                    @else
                        <p class="text-sm text-error-500">The attached assessment is unavailable.</p>
                    @endif
                    @break
                @default
                    @if($material->file_path)
                        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                            <p class="font-medium text-gray-800 dark:text-white">{{ $material->original_filename }}</p>
                            <a href="{{ route('learning.courses.materials.download', [$enrollment, $material]) }}" class="mt-4 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Download file</a>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No file is attached.</p>
                    @endif
            @endswitch
        </x-common.component-card>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>@if($previous)<a href="{{ route('learning.courses.materials.show', [$enrollment, $previous]) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">← Previous</a>@endif</div>
            <div class="flex gap-3">
                @if(! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at)
                    <form method="POST" action="{{ route('learning.courses.materials.complete', [$enrollment, $material]) }}">@csrf <button class="rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white">Mark complete</button></form>
                @else
                    <x-ui.badge color="success">Completed</x-ui.badge>
                @endif
                @if($next)<a href="{{ route('learning.courses.materials.show', [$enrollment, $next]) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Next →</a>@endif
            </div>
        </div>
    </main>
</div>
@endsection
