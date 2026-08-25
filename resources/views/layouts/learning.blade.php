<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __($title ?? 'Course player') }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
</head>

<body class="min-h-full bg-gray-50 text-gray-800 dark:bg-gray-950 dark:text-white">
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
                <a href="{{ route('learning.courses.index') }}" class="flex shrink-0 items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200" aria-label="Back to enrolled courses"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-lg font-bold text-white">L</span><span class="hidden sm:inline">LMS</span></a>
                <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>
                <p class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-800 dark:text-white">{{ $enrollment->course->title }}</p>
                @if(auth()->user()?->can('credit-scores.view-own'))<div class="hidden sm:block"><x-header.credit-summary /></div>@endif
                <div class="hidden items-center gap-3 md:flex"><div class="w-56 rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/[0.04]"><div class="flex items-end justify-between gap-2"><span class="text-[11px] font-semibold text-gray-700 dark:text-gray-200">Course progress</span><span class="text-sm font-bold text-brand-500">{{ $progressPercentage }}%</span></div><p class="mt-0.5 text-[11px] text-gray-600 dark:text-gray-400">{{ $progress['completed'] }} of {{ $progress['total'] }} course items complete</p><div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" style="width: {{ $progressPercentage }}%"></div></div></div><a href="{{ route('learning.courses.index') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300">Back to courses</a><a href="{{ route('learning.assessments.index') }}" class="hidden rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:text-brand-500 xl:inline">Tests</a><a href="{{ route('learning.credit-scores.index') }}" class="hidden rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:text-brand-500 xl:inline">Credit scores</a><a href="{{ route('account.profile.edit') }}" class="hidden rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:text-brand-500 xl:inline">Profile</a><a href="{{ route('learning.courses.index') }}" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-white dark:text-gray-900">Exit course</a></div>
                <button type="button" @click="outlineOpen = !outlineOpen" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 md:hidden dark:border-gray-700 dark:text-gray-300">Contents</button>
            </div>
        </header>

        <div class="mx-auto flex max-w-[1600px]">
            <aside class="fixed inset-y-16 left-0 z-30 w-[min(86vw,340px)] -translate-x-full overflow-y-auto border-r border-gray-200 bg-white p-5 transition-transform md:sticky md:top-16 md:block md:h-[calc(100vh-4rem)] md:w-80 md:translate-x-0 md:shrink-0 md:self-start dark:border-gray-800 dark:bg-gray-900" :class="outlineOpen ? 'translate-x-0' : ''">
                <div class="mb-6 flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-500">Course contents</p><h1 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $enrollment->course->title }}</h1></div><button type="button" @click="outlineOpen = false" class="text-xl text-gray-400 md:hidden" aria-label="Close contents">&times;</button></div>
                <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.04]"><div class="flex items-end justify-between"><span class="text-xs text-gray-600 dark:text-gray-400">{{ $progress['completed'] }} of {{ $progress['total'] }} course items complete</span><span class="text-lg font-bold text-brand-500">{{ $progressPercentage }}%</span></div><div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-500" style="width: {{ min(100, max(0, (float) $progressPercentage)) }}%"></div></div></div>
                <nav class="space-y-6">
                    @foreach($enrollment->course->modules as $module)
                        @php($moduleItems = $module->chapters->flatMap->materials)
                        @php($moduleRequired = $moduleItems->where('is_required', true))
                        @php($moduleCompleted = $moduleRequired->whereIn('id', $completedMaterialIds)->count())
                        <section x-data="{ expanded: true }"><div class="mb-3 flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Module {{ $loop->iteration }}</p><h2 class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h2><p class="mt-1 text-xs text-gray-500">{{ $moduleCompleted }} of {{ $moduleRequired->count() }} course items completed</p></div><button type="button" @click="expanded = !expanded" class="rounded p-1 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-white/[0.06]" :aria-expanded="expanded.toString()" aria-label="Toggle module contents"><span x-text="expanded ? '−' : '+'"></span></button></div><div x-show="expanded" x-collapse class="space-y-4">
                            @foreach($module->chapters as $chapter)
                                <div><p class="mb-1 px-2 text-xs font-medium text-gray-500">{{ $chapter->title }}</p><div class="space-y-1">
                                    @foreach($chapter->materials as $item)
                                        @php($done = $completedMaterialIds->contains($item->id))
                                        @php($itemIndex = $orderedMaterials->search(fn ($ordered) => (int) $ordered->id === (int) $item->id))
                                        @php($itemLocked = $enrollment->course->navigation_mode === \App\Enums\NavigationMode::Sequential && $orderedMaterials->take($itemIndex)->contains(fn ($ordered) => $ordered->is_required && ! $completedMaterialIds->contains($ordered->id)))
                                        @php($isAssessment = $item->type === \App\Enums\MaterialType::CourseAssessment)
                                        @php($stateLabel = $done ? 'Completed' : ($item->id === $material->id ? 'Current lesson' : ($itemLocked ? 'Locked — complete the previous required material first' : ($isAssessment ? 'Assessment — unlocked after all lessons' : 'Available'))))
                                        <a href="{{ route('learning.courses.materials.show', [$enrollment, $item]) }}" @click="outlineOpen = false" aria-label="{{ $item->title }} — {{ $stateLabel }}" class="flex items-start gap-2 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $item->id === $material->id ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : ($itemLocked ? 'text-gray-400 dark:text-gray-500' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.04]') }}"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] {{ $done ? 'border-success-500 bg-success-500 text-white' : 'border-gray-300 text-gray-400 dark:border-gray-700' }}">{{ $done ? '✓' : $loop->iteration }}</span><span class="min-w-0"><span class="flex items-center gap-1.5"><span class="block truncate" title="{{ $item->title }}">{{ $item->title }}</span>@if($isAssessment)<span class="shrink-0 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">Assessment</span>@endif</span><span class="mt-0.5 block text-[11px] {{ $item->id === $material->id ? 'text-brand-500' : 'text-gray-400' }}">{{ $stateLabel }}</span></span></a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div></section>
                    @endforeach
                </nav>
            </aside>

            <main class="min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-12">
                <div class="mx-auto max-w-2xl">
                    @if (session('success'))<div class="mb-6 rounded-xl border border-success-500/30 bg-success-50 px-4 py-3 text-sm text-success-700 dark:bg-success-500/15 dark:text-success-400">{{ session('success') }}</div>@endif
                    @if ($errors->any())<div class="mb-6 rounded-xl border border-error-500/30 bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">{{ $errors->first() }}</div>@endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
