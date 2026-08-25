@extends('layouts.learning-document')

@section('content')
@php
    $orderedMaterials = $enrollment->course->modules->flatMap->chapters->flatMap->materials->values();
    $completedMaterialIds = $enrollment->materialProgress->whereNotNull('completed_at')->pluck('learning_material_id');
    $progressPercentage = $progress['percentage'];
    $currentChapter = $enrollment->course->modules->flatMap->chapters->first(fn ($chapter) => $chapter->materials->contains('id', $material->id));
    $currentModule = $enrollment->course->modules->first(fn ($module) => $module->chapters->contains(fn ($chapter) => $chapter->materials->contains('id', $material->id)));
    $remainingMinutes = $orderedMaterials->filter(fn ($item) => ! $completedMaterialIds->contains($item->id))->sum(fn ($item) => (int) ($item->duration_minutes ?? 0));
@endphp
<div class="min-h-screen" x-data="{ outlineOpen: false, completing: false, completed: {{ $completedMaterialIds->contains($material->id) ? 'true' : 'false' }}, progressPercentage: {{ $progressPercentage }}, completionError: '', async completeLesson(event) { if (this.completing || this.completed) return; this.completing = true; this.completionError = ''; try { const response = await fetch(event.target.action, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': event.target.querySelector('[name=_token]').value } }); if (!response.ok) throw new Error('Completion failed'); const data = await response.json(); this.completed = data.completed; this.progressPercentage = data.progress_percentage; } catch (error) { this.completionError = 'We could not save this lesson yet. Please try again.'; } finally { this.completing = false; } } }">
    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
            <a href="{{ route('learning.courses.index') }}" class="flex shrink-0 items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200" aria-label="Back to enrolled courses">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-lg font-bold text-white">L</span>
                <span class="hidden sm:inline">Learning space</span>
            </a>
            <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
            <p class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-800 dark:text-white">{{ $enrollment->course->title }}</p>
            @if(auth()->user()?->can('credit-scores.view-own'))
                <div class="hidden sm:block"><x-header.credit-summary /></div>
            @endif
            <div class="hidden items-center gap-3 md:flex">
                <div class="w-56 rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/[0.04]"><div class="flex items-end justify-between gap-2"><span class="text-[11px] font-semibold text-gray-700 dark:text-gray-200">Course progress</span><span class="text-sm font-bold text-brand-500">{{ $progressPercentage }}%</span></div><p class="mt-0.5 text-[11px] text-gray-600 dark:text-gray-400">{{ $progress['completed'] }} of {{ $progress['total'] }} course items complete</p><div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ $progressPercentage }}%"></div></div></div>
                <div class="hidden">
                    <div class="mb-1 flex justify-between text-[11px] text-gray-500"><span>Progress</span><span>{{ $progress['completed'] }} / {{ $progress['total'] }} · {{ number_format((float) $enrollment->progress_percentage, 0) }}%</span></div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, max(0, (float) $progressPercentage)) }}%"></div></div>
                </div>
                <a href="{{ route('learning.courses.index') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300">Back to courses</a>
                <a href="{{ route('learning.assessments.index') }}" class="hidden rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:text-brand-500 xl:inline">Tests</a>
                <a href="{{ route('learning.credit-scores.index') }}" class="hidden rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:text-brand-500 xl:inline">Credit scores</a>
                <a href="{{ route('account.profile.edit') }}" class="hidden rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:text-brand-500 xl:inline">Profile</a>
                <a href="{{ route('learning.courses.index') }}" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-gray-900">Exit course</a>
            </div>
            <button type="button" @click="outlineOpen = !outlineOpen" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 md:hidden dark:border-gray-700 dark:text-gray-300">Contents</button>
        </div>
    </header>

    <div class="mx-auto flex max-w-[1600px]">
        <aside id="course-outline" class="fixed inset-y-16 left-0 z-30 w-[min(86vw,340px)] -translate-x-full overflow-y-auto border-r border-gray-200 bg-white p-5 transition-transform md:sticky md:top-16 md:block md:h-[calc(100vh-4rem)] md:w-80 md:translate-x-0 md:shrink-0 md:self-start dark:border-gray-800 dark:bg-gray-900" :class="outlineOpen ? 'translate-x-0' : ''">
            <div class="mb-6 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-500">Course contents</p>
                    <h1 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $enrollment->course->title }}</h1>
                </div>
                <button type="button" @click="outlineOpen = false" class="text-xl text-gray-400 md:hidden" aria-label="Close contents">&times;</button>
            </div>
            <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.04]">
                <div class="flex items-end justify-between"><span class="text-xs text-gray-600 dark:text-gray-400">{{ $progress['completed'] }} of {{ $progress['total'] }} course items complete</span><span class="text-lg font-bold text-brand-500">{{ $progressPercentage }}%</span></div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-500" style="width: {{ min(100, max(0, (float) $progressPercentage)) }}%"></div></div>
            </div>
            <nav class="space-y-6">
                @foreach($enrollment->course->modules as $module)
                    @php
                        $moduleItems = $module->chapters->flatMap->materials;
                        $moduleRequired = $moduleItems->where('is_required', true);
                        $moduleCompleted = $moduleRequired->whereIn('id', $completedMaterialIds)->count();
                    @endphp
                    <section x-data="{ expanded: true }">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Module {{ $loop->iteration }}</p><h2 class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h2><p class="mt-1 text-xs text-gray-500">{{ $moduleCompleted }} of {{ $moduleRequired->count() }} course items completed</p></div>
                            <button type="button" @click="expanded = !expanded" class="rounded p-1 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-white/[0.06]" :aria-expanded="expanded.toString()" aria-label="Toggle module contents"><span x-text="expanded ? '−' : '+'"></span></button>
                        </div>
                        <div x-show="expanded" x-collapse class="space-y-4">
                            @foreach($module->chapters as $chapter)
                                <div id="chapter-{{ $chapter->id }}" x-init="if ({{ $chapter->id === $currentChapter?->id ? 'true' : 'false' }}) { $nextTick(() => { const outline = $el.closest('aside'); if (outline) outline.scrollTo({ top: Math.max(0, $el.offsetTop - (outline.clientHeight / 3)), behavior: 'auto' }); }); }" class="{{ $chapter->id === $currentChapter?->id ? 'rounded-xl bg-brand-50/60 p-2 dark:bg-brand-500/10' : '' }}">
                                    <p class="mb-1 px-2 text-xs font-medium text-gray-500">{{ $chapter->title }}</p>
                                    <div class="space-y-1">
                                        @foreach($chapter->materials as $item)
                                            @php
                                                $done = $completedMaterialIds->contains($item->id);
                                                $itemIndex = $orderedMaterials->search(fn ($ordered) => (int) $ordered->id === (int) $item->id);
                                                $itemLocked = $enrollment->course->navigation_mode === \App\Enums\NavigationMode::Sequential && $orderedMaterials->take($itemIndex)->contains(fn ($ordered) => $ordered->is_required && ! $completedMaterialIds->contains($ordered->id));
                                                $isAssessment = $item->type === \App\Enums\MaterialType::CourseAssessment;
                                                $stateLabel = $done ? 'Completed' : ($item->id === $material->id ? 'Current lesson' : ($itemLocked ? 'Locked — complete the previous required material first' : ($isAssessment ? 'Assessment — unlocked after all lessons' : 'Available')));
                                            @endphp
                                            <a href="{{ route('learning.courses.materials.show', [$enrollment, $item]) }}" @click="outlineOpen = false" aria-label="{{ $item->title }} — {{ $stateLabel }}" class="flex items-start gap-2 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $item->id === $material->id ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : ($itemLocked ? 'text-gray-400 dark:text-gray-500' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.04]') }}">
                                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] {{ $done ? 'border-success-500 bg-success-500 text-white' : 'border-gray-300 text-gray-400 dark:border-gray-700' }}">{{ $done ? '✓' : $loop->iteration }}</span>
                                                <span class="min-w-0"><span class="flex items-center gap-1.5"><span class="block truncate" title="{{ $item->title }}">{{ $item->title }}</span>@if($isAssessment)<span class="shrink-0 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">Assessment</span>@endif</span><span class="mt-0.5 block text-[11px] {{ $item->id === $material->id ? 'text-brand-500' : 'text-gray-400' }}">{{ $stateLabel }}</span></span>
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
            <div class="mx-auto max-w-2xl">
                @if(session('credit_award_id'))
                    <div x-data="{ open: true }" x-show="open" x-cloak class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
                        <div class="flex items-start justify-between gap-4"><div><p class="font-semibold text-brand-800 dark:text-brand-200">Claim your course credit score</p><p class="mt-1 text-sm text-brand-700 dark:text-brand-300">You completed this course and have <strong>+{{ number_format($progress['creditPoints'], 2) }} credits</strong> ready to claim.</p></div><button type="button" @click="open = false" class="text-brand-600" aria-label="Close">&times;</button></div>
                        <form method="POST" action="{{ route('learning.credit-scores.claim', session('credit_award_id')) }}" class="mt-4">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Claim {{ number_format($progress['creditPoints'], 2) }} credits</button></form>
                    </div>
                @endif

                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $currentModule?->title ?? 'Course module' }} @if($currentChapter) · {{ $currentChapter->title }} @endif</p>
                    <p class="text-sm font-medium text-brand-500">{{ $material->type->value === 'course_assessment' ? 'Knowledge check' : 'Lesson' }}</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ $material->title }}</h2>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-500"><span>Type: {{ str_replace('_', ' ', ucfirst($material->type->value)) }}</span><span>Estimated: {{ (int) ($material->duration_minutes ?? 0) }} min</span><span>About {{ $remainingMinutes }} min remaining</span></div>
                    @if($material->description)<p class="mt-3 max-w-3xl text-base leading-7 text-gray-500">{{ $material->description }}</p>@endif
                </div>

                @if($locked)
                    <article class="rounded-2xl border border-warning-200 bg-warning-50 p-6 dark:border-warning-500/30 dark:bg-warning-500/10 sm:p-10">
                        <p class="text-sm font-semibold uppercase tracking-wide text-warning-700 dark:text-warning-300">{{ __('Lesson locked') }}</p>
                        <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Complete the previous required lesson first') }}</h3>
                        <p class="mt-3 text-sm leading-6 text-warning-800 dark:text-warning-200">{{ __('This lesson will unlock after you complete:') }} <strong>{{ $blockingMaterial->title }}</strong></p>
                        <div class="mt-5 h-2 overflow-hidden rounded-full bg-warning-200 dark:bg-warning-500/20"><div class="h-full rounded-full bg-warning-500" style="width: {{ $progress['total'] > 0 ? min(100, ($progress['completed'] / $progress['total']) * 100) : 0 }}%"></div></div>
                        <p class="mt-2 text-xs text-warning-700 dark:text-warning-300">{{ $progress['completed'] }} {{ __('of') }} {{ $progress['total'] }} {{ __('required course items completed') }}</p>
                        <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('learning.courses.materials.show', [$enrollment, $blockingMaterial]) }}" class="inline-flex rounded-lg bg-warning-500 px-4 py-2.5 text-sm font-medium text-white">{{ __('Go to previous lesson') }}</a><a href="{{ route('learning.courses.index') }}" class="inline-flex rounded-lg border border-warning-300 px-4 py-2.5 text-sm font-medium text-warning-800 dark:border-warning-500/40 dark:text-warning-200">{{ __('Back to course contents') }}</a></div>
                    </article>
                @else
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
                                @php($assessmentAttempts = $material->courseAssessment?->attempts?->where('user_id', auth()->id()) ?? collect())
                                <div class="rounded-xl border border-brand-200 bg-brand-50 p-6 dark:border-brand-500/30 dark:bg-brand-500/10"><p class="font-semibold text-gray-800 dark:text-white">Course assessment</p><p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $material->courseAssessment?->questions?->count() ?? 0 }} questions · Passing score: {{ $material->courseAssessment?->passing_percentage ?? 60 }}% · Attempts allowed: Unlimited</p><p class="mt-2 text-xs font-medium {{ $assessmentAttempts->contains(fn ($attempt) => $attempt->passed) ? 'text-success-700' : 'text-brand-700' }}">Status: {{ $assessmentAttempts->contains(fn ($attempt) => $attempt->passed) ? 'Passed' : 'Available' }}</p>@if(! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at && $material->courseAssessment)<form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$enrollment, $material]) }}" class="mt-4" @submit="opening = true">@csrf<button type="submit" :disabled="opening" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-60"><span x-show="!opening">Start assessment</span><span x-cloak x-show="opening">Opening assessment…</span></button></form>@endif</div>
                                @break
                            @default
                                <p class="text-sm text-gray-500">This learning material is unavailable.</p>
                        @endswitch
                    </div>
                </article>
                @endif

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div>@if($previous)<a href="{{ route('learning.courses.materials.show', [$enrollment, $previous]) }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">← Previous</a>@endif</div>
                    <div class="flex items-center gap-3">
                        @if(! $locked && ! $enrollment->materialProgress->firstWhere('learning_material_id', $material->id)?->completed_at)
                            @if($material->type !== \App\Enums\MaterialType::CourseAssessment)
                                <form x-show="!completed" method="POST" action="{{ route('learning.courses.materials.complete', [$enrollment, $material]) }}" @submit.prevent="completeLesson($event)">@csrf<button type="submit" :disabled="completing" class="rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white disabled:cursor-wait disabled:opacity-60"><span x-show="!completing">Mark as complete</span><span x-cloak x-show="completing">Saving…</span></button><p x-show="completionError" x-text="completionError" class="mt-2 text-xs text-error-600" role="alert"></p></form>
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
