@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$course->title" :translate="false">
    <x-slot:actions>
        @can('courses.show')<a href="{{ route(\App\Support\PortalRoute::name('courses.preview'), $course) }}" class="rounded-lg border border-brand-300 bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">{{ __('Preview course') }}</a>@endcan
        @can('courses.edit')<a href="{{ route(\App\Support\PortalRoute::name('courses.edit'), $course) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Edit details</a>@endcan
        @can('courses.publish')
            <form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.status'), $course) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $course->status->value === 'published' ? 'archived' : 'published' }}">
        <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ __($course->status->value === 'published' ? 'Archive' : 'Publish') }}</button>
            </form>
        @endcan
        @can('courses.delete')
            <form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.destroy'), $course) }}" onsubmit="return confirm('Delete this course and curriculum?')">
                @csrf @method('DELETE')
                <button class="rounded-lg bg-error-50 px-4 py-2.5 text-sm text-error-600">Delete</button>
            </form>
        @endcan
    </x-slot:actions>
</x-common.page-breadcrumb>

@php
    $courseModules = $course->modules;
    $courseChapters = $courseModules->flatMap->chapters;
    $courseMaterials = $courseChapters->flatMap->materials;
    $assessmentQuestions = $courseMaterials->sum(fn ($material) => $material->courseAssessment?->questions->count() ?? 0);
    $readyModules = $courseModules->filter(fn ($module) => $module->chapters->isNotEmpty() && $module->chapters->every(fn ($chapter) => $chapter->materials->isNotEmpty()))->count();
    $readyChapters = $courseChapters->filter(fn ($chapter) => $chapter->materials->isNotEmpty())->count();
@endphp
<x-common.component-card title="Course builder progress" desc="Keep the curriculum complete before publishing." class="mb-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['label' => 'Modules completed', 'value' => $readyModules.'/'.$courseModules->count()],
            ['label' => 'Chapters completed', 'value' => $readyChapters.'/'.$courseChapters->count()],
            ['label' => 'Materials added', 'value' => (string) $courseMaterials->count()],
            ['label' => 'Assessment questions', 'value' => (string) $assessmentQuestions],
            ['label' => 'Publishing readiness', 'value' => $publishIssues === [] ? 'Ready to publish' : count($publishIssues).' issue(s)'],
        ] as $metric)
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __($metric['label']) }}</p><p class="mt-2 text-lg font-semibold {{ $metric['label'] === 'Publishing readiness' && $publishIssues === [] ? 'text-success-600' : 'text-gray-800 dark:text-white' }}">{{ $metric['value'] }}</p></div>
        @endforeach
    </div>
</x-common.component-card>

@if($publishIssues !== [])
    <x-common.component-card title="Course completeness" desc="Resolve these items before publishing the course." class="mb-6">
        <ul class="space-y-3 text-sm">
            @foreach($publishIssues as $issue)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                    <span>{{ $issue['message'] }}</span>
                    @if(isset($issue['assessment']) && $issue['assessment'])
                        <a href="{{ route(\App\Support\PortalRoute::name('course-assessments.show'), $issue['assessment']) }}#chapter-{{ $issue['chapter_id'] }}" class="font-medium text-brand-600 underline dark:text-brand-300">{{ __('Open assessment') }}</a>
                    @elseif(isset($issue['chapter_id']))
                        <a href="#chapter-{{ $issue['chapter_id'] }}" class="font-medium text-brand-600 underline dark:text-brand-300">{{ __('Open chapter') }}</a>
                    @elseif(isset($issue['module_id']))
                        <a href="#module-{{ $issue['module_id'] }}" class="font-medium text-brand-600 underline dark:text-brand-300">{{ __('Open module') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-common.component-card>
@endif

<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <div class="space-y-6" x-data>
        <x-common.component-card title="Curriculum" desc="Expand modules and chapters to edit them. Drag the handle to reorder items; changes save automatically.">
            <div class="space-y-5" x-data="curriculumReorder({ csrfToken: @js(csrf_token()) })">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                    <p class="text-sm text-gray-500">{{ __('Manage modules, chapters, and learning materials from one place.') }}</p>
                    <div class="flex gap-2"><button type="button" @click="$dispatch('expand-all-modules')" class="rounded border border-gray-300 px-3 py-2 text-xs font-medium dark:border-gray-700 dark:text-white">{{ __('Expand all') }}</button><button type="button" @click="$dispatch('collapse-all-modules')" class="rounded border border-gray-300 px-3 py-2 text-xs font-medium dark:border-gray-700 dark:text-white">{{ __('Collapse all') }}</button></div>
                </div>
                <p x-show="statusMessage" x-cloak x-text="statusMessage" class="text-sm" :class="{
                    'text-gray-500': statusType === 'saving',
                    'text-success-600': statusType === 'success',
                    'text-error-600': statusType === 'error'
                }" role="status" aria-live="polite"></p>
                <div x-ref="modules" data-module-list @can('modules.reorder') data-reorder-url="{{ route(\App\Support\PortalRoute::name('course-modules.reorder'), $course) }}" @endcan class="space-y-5">
                @forelse($course->modules as $module)
                    <section id="module-{{ $module->id }}" data-module-id="{{ $module->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-800" x-data="{ expanded: false }" x-init="const saved = JSON.parse(localStorage.getItem('course-{{ $course->id }}-expanded-modules') || '[]'); expanded = window.location.hash === '#module-{{ $module->id }}' || @js($module->chapters->pluck('id')->all()).some(id => window.location.hash === '#chapter-' + id) || saved.includes({{ $module->id }}); $watch('expanded', value => { const ids = JSON.parse(localStorage.getItem('course-{{ $course->id }}-expanded-modules') || '[]'); const next = value ? [...new Set([...ids, {{ $module->id }}])] : ids.filter(id => id !== {{ $module->id }}); localStorage.setItem('course-{{ $course->id }}-expanded-modules', JSON.stringify(next)); })" @expand-all-modules.window="expanded = true" @collapse-all-modules.window="expanded = false" @focus-curriculum-module.window="expanded = $event.detail.moduleId === {{ $module->id }}" @focus-curriculum-chapter.window="expanded = $event.detail.moduleId === {{ $module->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                @can('modules.reorder')<button type="button" class="handle mt-1 flex h-8 w-8 cursor-grab items-center justify-center rounded border border-gray-300 text-gray-500 active:cursor-grabbing dark:border-gray-700" title="Drag to reorder module" aria-label="Drag to reorder module"><i class="bi bi-arrows-move" aria-hidden="true"></i></button>@endcan
                                <div class="text-left">
                                    <p data-module-number class="text-xs font-semibold uppercase text-brand-500">Module {{ $loop->iteration }}</p>
                                    <h3 class="font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ $module->chapters->filter(fn ($chapter) => $chapter->materials->isNotEmpty())->count() }}/{{ $module->chapters->count() }} {{ __('chapters ready') }} · {{ $module->chapters->sum(fn ($chapter) => $chapter->materials->count()) }} {{ __('materials') }}</p>
                                    @if($module->description)<p class="text-sm text-gray-500">{{ $module->description }}</p>@endif
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button type="button" class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-500 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" @click.stop="expanded ? expanded = false : $dispatch('focus-curriculum-module', { moduleId: {{ $module->id }} })" :aria-expanded="expanded.toString()" aria-controls="module-panel-{{ $module->id }}" title="Toggle module" aria-label="Toggle module">
                                    <i class="bi text-sm transition-transform duration-300" :class="expanded ? 'bi-dash' : 'bi-plus'" aria-hidden="true"></i>
                                </button>
                                @can('modules.edit')<button type="button" @click="$dispatch('focus-curriculum-module', { moduleId: {{ $module->id }} }); $dispatch('open-module-edit-modal', { moduleId: {{ $module->id }} })" class="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:text-white">{{ __('Edit') }} {{ __('Module') }} {{ $loop->iteration }}</button>@endcan
                                @can('modules.delete')
                                    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.destroy'), $module) }}" onsubmit="return confirm('Delete this module, its chapters, and all materials?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">{{ __('Delete') }} {{ __('Module') }} {{ $loop->iteration }}</button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        <div id="module-panel-{{ $module->id }}" x-show="expanded" x-collapse.duration.300ms x-cloak class="mt-4">
                        <div class="mt-5 space-y-4">
                            <div data-chapter-list @can('chapters.reorder') data-reorder-url="{{ route(\App\Support\PortalRoute::name('course-chapters.reorder'), $module) }}" @endcan class="space-y-4">
                            @forelse($module->chapters as $chapter)
                                <section id="chapter-{{ $chapter->id }}" data-chapter-id="{{ $chapter->id }}" class="rounded-xl bg-gray-50 p-4 dark:bg-white/[0.02]" x-data="{ expanded: false }" x-init="expanded = window.location.hash === '#chapter-{{ $chapter->id }}'" @focus-curriculum-module.window="expanded = false" @focus-curriculum-chapter.window="expanded = $event.detail.chapterId === {{ $chapter->id }}">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-start gap-3">
                                            @can('chapters.reorder')<button type="button" class="handle mt-1 flex h-8 w-8 cursor-grab items-center justify-center rounded border border-gray-300 bg-white text-gray-500 active:cursor-grabbing dark:border-gray-700 dark:bg-gray-900" title="Drag to reorder chapter" aria-label="Drag to reorder chapter"><i class="bi bi-arrows-move" aria-hidden="true"></i></button>@endcan
                                            <div class="text-left">
                                                <p data-chapter-number class="text-xs font-semibold uppercase text-gray-500">Chapter {{ $loop->iteration }}</p>
                                                <div class="flex flex-wrap items-center gap-2"><h4 class="font-medium text-gray-800 dark:text-white">{{ $chapter->title }}</h4>@if($chapter->materials->isEmpty())<x-ui.badge color="warning">{{ __('Incomplete') }}</x-ui.badge>@else<x-ui.badge color="success">{{ $chapter->materials->count() }} {{ __('materials') }}</x-ui.badge>@endif</div>
                                                @if($chapter->description)<p class="text-sm text-gray-500">{{ $chapter->description }}</p>@endif
                                            </div>
                                        </div>
                                        <div class="flex gap-1">
                                            <button type="button" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-500 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]" @click.stop="expanded ? expanded = false : $dispatch('focus-curriculum-chapter', { moduleId: {{ $module->id }}, chapterId: {{ $chapter->id }} })" :aria-expanded="expanded.toString()" aria-controls="chapter-panel-{{ $chapter->id }}" title="Toggle chapter" aria-label="Toggle chapter">
                                                <i class="bi text-sm transition-transform duration-300" :class="expanded ? 'bi-dash' : 'bi-plus'" aria-hidden="true"></i>
                                            </button>
                                            @can('chapters.edit')<button type="button" @click="$dispatch('focus-curriculum-chapter', { moduleId: {{ $module->id }}, chapterId: {{ $chapter->id }} }); $dispatch('open-chapter-edit-modal', { chapterId: {{ $chapter->id }} })" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ __('Edit') }} {{ __('Chapter') }} {{ $loop->iteration }}</button>@endcan
                                            @can('chapters.delete')
                                                <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.destroy'), $chapter) }}" onsubmit="return confirm('Delete this empty chapter?')">
                                                    @csrf @method('DELETE')
                                                    <button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">{{ __('Delete') }} {{ __('Chapter') }} {{ $loop->iteration }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>

                                    <div id="chapter-panel-{{ $chapter->id }}" x-show="expanded" x-collapse.duration.300ms x-cloak>
                                    <div data-material-list @can('materials.reorder') data-reorder-url="{{ route(\App\Support\PortalRoute::name('learning-materials.reorder'), $chapter) }}" @endcan class="mt-3 space-y-2">
                                        @forelse($chapter->materials as $material)
                                            @php
                                                $materialIcon = match ($material->type) {
                                                    \App\Enums\MaterialType::Article => 'bi-file-earmark-text',
                                                    \App\Enums\MaterialType::Video => 'bi-camera-video',
                                                    \App\Enums\MaterialType::File => match ($material->file_type) {
                                                        'pdf' => 'bi-file-earmark-pdf',
                                                        'docx' => 'bi-file-earmark-word',
                                                        'pptx' => 'bi-file-earmark-slides',
                                                        default => 'bi-download',
                                                    },
                                                    \App\Enums\MaterialType::Link => 'bi-link-45deg',
                                                    \App\Enums\MaterialType::CourseAssessment => 'bi-clipboard-check',
                                                    default => 'bi-file-earmark',
                                                };
                                            @endphp
                                            <div data-material-id="{{ $material->id }}" class="group flex flex-wrap items-center justify-between gap-3 rounded-xl border border-transparent px-3 py-3 transition-all duration-200 hover:border-brand-200 hover:bg-brand-50/60 hover:shadow-sm dark:hover:border-brand-500/30 dark:hover:bg-brand-500/5">
                                                <div class="flex min-w-0 items-center gap-3">
                                                    @can('materials.reorder')<button type="button" class="handle flex h-8 w-8 shrink-0 cursor-grab items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-white hover:text-brand-500 active:cursor-grabbing dark:hover:bg-gray-900" title="Drag to reorder material" aria-label="Drag to reorder material"><i class="bi bi-arrows-move" aria-hidden="true"></i></button>@endcan
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg text-brand-500 shadow-sm transition-transform duration-200 group-hover:scale-105 dark:bg-gray-800"><i class="bi {{ $materialIcon }}" aria-hidden="true"></i></span>
                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span data-material-number class="text-xs font-semibold uppercase tracking-wide text-brand-500">Page {{ $loop->iteration }}</span>
                                                            <p class="text-sm font-medium text-gray-800 transition-colors group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">{{ $material->title }}</p>
                                                        </div>
                                                        <p class="text-xs text-gray-500">{{ $material->type->label() }} &middot; {{ $material->is_required ? 'Required' : 'Optional' }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex gap-1">
                                                    @if($material->courseAssessment && request()->routeIs('instructor.*', 'super-admin.*') && auth()->user()->can('course-assessments.questions.manage'))<a href="{{ route(\App\Support\PortalRoute::name('course-assessments.show'), $material->courseAssessment) }}" class="rounded border border-brand-300 bg-brand-50 px-2 py-1 text-xs text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">{{ __('Questions') }}@if($material->courseAssessment->questions->isEmpty()) · {{ __('Incomplete') }}@endif</a>@endif
                                                    @can('materials.edit')<a href="{{ route(\App\Support\PortalRoute::name('learning-materials.edit'), $material) }}" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ __('Edit') }} {{ __('Material') }}</a>@endcan
                                                    @can('materials.delete')
                                                        <form method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.destroy'), $material) }}" onsubmit="return confirm('Delete this material?')">
                                                            @csrf @method('DELETE')
                                                            <button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">{{ __('Delete') }} {{ __('Material') }}</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        @empty
                                            <p class="py-4 text-sm text-gray-500">No learning materials in this chapter.</p>
                                        @endforelse
                                    </div>

                                    @can('materials.create')
                                        <a href="{{ route(\App\Support\PortalRoute::name('learning-materials.create'), $chapter) }}" class="mt-2 inline-flex text-sm font-medium text-brand-500">+ {{ __('Add material to') }} {{ $chapter->title }}</a>
                                    @endcan
                                    </div>

                                    @can('chapters.edit')
                                        <x-ui.modal @open-chapter-edit-modal.window="if ($event.detail.chapterId === {{ $chapter->id }}) open = true" :isOpen="false" class="max-w-[640px]">
                                            <div class="p-6 pr-14">
                                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Edit Chapter</h4>
                                                <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.update'), $chapter) }}" class="mt-5 space-y-4">
                                                    @csrf @method('PUT')
                                                    <x-form.input :id="'chapter-title-'.$chapter->id" name="title" label="Chapter title" :value="$chapter->title" required />
                                                    <x-form.textarea :id="'chapter-description-'.$chapter->id" name="description" label="Description" :value="$chapter->description" rows="3" />
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</button>
                                                        <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-ui.modal>
                                    @endcan
                                </section>
                            @empty
                                <p class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700">No chapters yet.</p>
                            @endforelse
                            </div>

                            @can('chapters.create')
                                <button type="button" @click="$dispatch('open-chapter-create-modal', { moduleId: {{ $module->id }} })" class="inline-flex items-center rounded-lg border border-dashed border-brand-300 px-4 py-2.5 text-sm font-medium text-brand-600 transition-colors hover:border-brand-500 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                    <i class="bi bi-plus-lg mr-2" aria-hidden="true"></i>{{ __('Add chapter to') }} {{ $module->title }}
                                </button>

                                <x-ui.modal @open-chapter-create-modal.window="if ($event.detail.moduleId === {{ $module->id }}) open = true" :isOpen="false" class="max-w-[640px]">
                                    <div class="p-6 pr-14">
                                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Add Chapter</h4>
                                        <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.store'), $module) }}" class="mt-5 space-y-4">
                                            @csrf
                                            <x-form.input :id="'create-chapter-title-'.$module->id" name="title" label="Chapter title" required />
                                            <x-form.textarea :id="'create-chapter-description-'.$module->id" name="description" label="Description" rows="3" />
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</button>
                                                <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Add chapter</button>
                                            </div>
                                        </form>
                                    </div>
                                </x-ui.modal>
                            @endcan
                        </div>
                        </div>

                        @can('modules.edit')
                            <x-ui.modal @open-module-edit-modal.window="if ($event.detail.moduleId === {{ $module->id }}) open = true" :isOpen="false" class="max-w-[640px]">
                                <div class="p-6 pr-14">
                                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Edit Module</h4>
                                    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.update'), $module) }}" class="mt-5 space-y-4">
                                        @csrf @method('PUT')
                                        <x-form.input :id="'module-title-'.$module->id" name="title" label="Module title" :value="$module->title" required />
                                        <x-form.textarea :id="'module-description-'.$module->id" name="description" label="Description" :value="$module->description" rows="3" />
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</button>
                                            <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </x-ui.modal>
                        @endcan
                    </section>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">No modules yet. Add the first module below.</p>
                @endforelse
                </div>

                @can('modules.create')
                    <button type="button" @click="$dispatch('open-module-create-modal')" class="inline-flex items-center rounded-lg border border-dashed border-brand-300 px-4 py-2.5 text-sm font-medium text-brand-600 transition-colors hover:border-brand-500 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10">
                        <i class="bi bi-plus-lg mr-2" aria-hidden="true"></i>{{ __('Add module') }}
                    </button>

                    <x-ui.modal @open-module-create-modal.window="open = true" :isOpen="false" class="max-w-[640px]">
                        <div class="p-6 pr-14">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Add Module</h4>
                            <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.store'), $course) }}" class="mt-5 space-y-4">
                                @csrf
                                <x-form.input name="title" label="Module title" required />
                                <x-form.textarea name="description" label="Description" rows="3" />
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</button>
                                    <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">{{ __('Add module') }}</button>
                                </div>
                            </form>
                        </div>
                    </x-ui.modal>
                @endcan
            </div>
        </x-common.component-card>
    </div>

    <aside class="space-y-6">
        <x-common.component-card title="Course overview">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><x-ui.badge :color="$course->status->value === 'published' ? 'success' : 'warning'">{{ $course->status->value }}</x-ui.badge></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Instructor</dt><dd class="text-right text-gray-800 dark:text-white">{{ $course->instructor?->name ?? 'Unassigned' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Category</dt><dd class="text-gray-800 dark:text-white">{{ $course->category?->name ?? 'None' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Navigation</dt><dd class="capitalize text-gray-800 dark:text-white">{{ $course->navigation_mode->value }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Enrollments</dt><dd class="text-gray-800 dark:text-white">{{ $course->enrollments_count }}</dd></div>
            </dl>
        </x-common.component-card>
        <x-common.component-card title="Description"><p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $course->description ?: $course->short_description }}</p></x-common.component-card>
    </aside>
</div>
@endsection
