@extends('layouts.learning')

@section('content')
<div class="min-h-screen" x-data="{ outlineOpen: false }">
    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
            <a href="{{ route('learning.courses.index') }}" class="flex shrink-0 items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200" aria-label="Back to enrolled courses">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-lg font-bold text-white">L</span>
                <span class="hidden sm:inline">Learning space</span>
            </a>
            <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
            <p class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-800 dark:text-white">{{ $enrollment->course->title }}</p>
            <div class="hidden items-center gap-3 md:flex">
                <div class="w-36">
                    <div class="mb-1 flex justify-between text-[11px] text-gray-500"><span>Progress</span><span>{{ number_format((float) $enrollment->progress_percentage, 0) }}%</span></div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, max(0, (float) $enrollment->progress_percentage)) }}%"></div></div>
                </div>
                <a href="{{ route('learning.courses.index') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300">Exit course</a>
            </div>
            <button type="button" @click="outlineOpen = !outlineOpen" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 md:hidden dark:border-gray-700 dark:text-gray-300">Contents</button>
        </div>
    </header>

    <div class="mx-auto flex max-w-[1600px]">
        <aside class="fixed inset-y-16 left-0 z-30 w-[min(86vw,340px)] -translate-x-full overflow-y-auto border-r border-gray-200 bg-white p-5 transition-transform md:sticky md:top-16 md:block md:h-[calc(100vh-4rem)] md:w-80 md:translate-x-0 md:shrink-0 md:self-start dark:border-gray-800 dark:bg-gray-900" :class="outlineOpen ? 'translate-x-0' : ''">
            <div class="mb-6 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-500">Course contents</p>
                    <h1 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $enrollment->course->title }}</h1>
                </div>
                <button type="button" @click="outlineOpen = false" class="text-xl text-gray-400 md:hidden" aria-label="Close contents">&times;</button>
            </div>
            <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.04]">
                <div class="flex items-end justify-between"><span class="text-xs text-gray-500">Your progress</span><span class="text-lg font-bold text-brand-500">{{ number_format((float) $enrollment->progress_percentage, 0) }}%</span></div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-500" style="width: {{ min(100, max(0, (float) $enrollment->progress_percentage)) }}%"></div></div>
            </div>
            <nav class="space-y-6">
                @foreach($enrollment->course->modules as $module)
                    <section>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Section {{ $loop->iteration }}</p>
                        <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h2>
                        <div class="space-y-4">
                            @foreach($module->chapters as $chapter)
                                <div>
                                    <p class="mb-1 px-2 text-xs font-medium text-gray-500">{{ $chapter->title }}</p>
                                    <div class="space-y-1">
                                        @foreach($chapter->materials as $item)
                                            @php($done = $enrollment->materialProgress->firstWhere('learning_material_id', $item->id)?->completed_at)
                                            <a href="{{ route('learning.courses.materials.show', [$enrollment, $item]) }}" @click="outlineOpen = false" class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm {{ $item->id === $material->id ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.04]' }}">
                                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] {{ $done ? 'border-success-500 bg-success-500 text-white' : 'border-gray-300 text-gray-400 dark:border-gray-700' }}">{{ $done ? '✓' : $loop->iteration }}</span>
                                                <span class="min-w-0 truncate">{{ $item->title }}</span>
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

        <main class="min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-4xl">
                @if(session('credit_award_id'))
                    <div x-data="{ open: true }" x-show="open" x-cloak class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
                        <div class="flex items-start justify-between gap-4"><div><p class="font-semibold text-brand-800 dark:text-brand-200">Claim this credit score</p><p class="mt-1 text-sm text-brand-700 dark:text-brand-300">You completed this course and have a credit score ready to claim.</p></div><button type="button" @click="open = false" class="text-brand-600" aria-label="Close">&times;</button></div>
                        <form method="POST" action="{{ route('learning.credit-scores.claim', session('credit_award_id')) }}" class="mt-4">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Claim credit score</button></form>
                    </div>
                @endif

                <div class="mb-8">
                    <p class="text-sm font-medium text-brand-500">{{ $material->type->value === 'course_assessment' ? 'Knowledge check' : 'Lesson' }}</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ $material->title }}</h2>
                    @if($material->description)<p class="mt-3 max-w-3xl text-base leading-7 text-gray-500">{{ $material->description }}</p>@endif
                </div>

                <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="p-6 sm:p-10">
                        @switch($material->type)
                            @case(\App\Enums\MaterialType::Article)
                                <div class="prose max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">{!! $material->content !!}</div>
                                @break
                            @case(\App\Enums\MaterialType::Video)
                                @if($material->content)<div class="prose mb-6 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">{!! $material->content !!}</div>@endif
                                @if($material->video_url)<a href="{{ $material->video_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Open video</a>@endif
                                @if($material->file_path)<a href="{{ route('learning.courses.materials.download', [$enrollment, $material]) }}" class="ml-2 inline-flex rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Download video</a>@endif
                                @break
                            @case(\App\Enums\MaterialType::File)
                                @if($material->content)<div class="prose mb-6 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">{!! $material->content !!}</div>@endif
                                @if($material->file_path)
                                    <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700"><p class="font-medium text-gray-800 dark:text-white">{{ $material->original_filename }}</p><p class="mt-1 text-sm uppercase text-gray-500">{{ $material->file_type === 'legacy' ? 'File' : $material->file_type }}</p><a href="{{ route('learning.courses.materials.download', [$enrollment, $material]) }}" class="mt-4 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Download file</a></div>
                                @else
                                    <p class="text-sm text-error-500">The learning file is unavailable.</p>
                                @endif
                                @break
                            @case(\App\Enums\MaterialType::Link)
                                @if($material->content)<div class="prose mb-6 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">{!! $material->content !!}</div>@endif
                                <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Open external resource</a>
                                @break
                            @case(\App\Enums\MaterialType::CourseAssessment)
                                @if($material->content)<div class="prose mb-6 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">{!! $material->content !!}</div>@endif
                                <div class="rounded-xl border border-brand-200 bg-brand-50 p-6 dark:border-brand-500/30 dark:bg-brand-500/10"><p class="font-semibold text-gray-800 dark:text-white">Course assessment</p><p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Pass with {{ $material->courseAssessment?->passing_percentage ?? 60 }}% or higher. You can retake it until you pass.</p>@if(! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at && $material->courseAssessment)<form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$enrollment, $material]) }}" class="mt-4">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Start assessment</button></form>@endif</div>
                                @break
                            @default
                                <p class="text-sm text-gray-500">This learning material is unavailable.</p>
                        @endswitch
                    </div>
                </article>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div>@if($previous)<a href="{{ route('learning.courses.materials.show', [$enrollment, $previous]) }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">← Previous</a>@endif</div>
                    <div class="flex items-center gap-3">
                        @if(! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at)
                            @if($material->type !== \App\Enums\MaterialType::CourseAssessment)
                                <form method="POST" action="{{ route('learning.courses.materials.complete', [$enrollment, $material]) }}">@csrf<button class="rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white">Mark complete</button></form>
                            @else
                                <span class="text-sm text-gray-500">Pass the assessment to complete this lesson.</span>
                            @endif
                        @else
                            <x-ui.badge color="success">Completed</x-ui.badge>
                        @endif
                        @if($next)<a href="{{ route('learning.courses.materials.show', [$enrollment, $next]) }}" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Next lesson <span class="ml-2">→</span></a>@endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
