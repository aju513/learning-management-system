@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$course->title">
    <x-slot:actions>
        @can('courses.edit')<a href="{{ route(\App\Support\PortalRoute::name('courses.edit'), $course) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Edit details</a>@endcan
        @can('courses.publish')
            <form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.status'), $course) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $course->status->value === 'published' ? 'archived' : 'published' }}">
                <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $course->status->value === 'published' ? 'Archive' : 'Publish' }}</button>
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

<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <div class="space-y-6">
        <x-common.component-card title="Curriculum" desc="Modules, chapters, and materials appear to trainees in this order.">
            <div class="space-y-5">
                @forelse($course->modules as $module)
                    <section id="module-{{ $module->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-800" x-data="{ editing: false }">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase text-brand-500">Module {{ $loop->iteration }}</p>
                                <h3 class="font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h3>
                                @if($module->description)<p class="text-sm text-gray-500">{{ $module->description }}</p>@endif
                            </div>
                            <div class="flex gap-1">
                                @can('modules.reorder')
                                    @foreach(['up' => '&uarr;', 'down' => '&darr;'] as $direction => $symbol)
                                        <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.move'), $module) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="direction" value="{{ $direction }}">
                                            <button class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:text-white" title="Move {{ $direction }}">{!! $symbol !!}</button>
                                        </form>
                                    @endforeach
                                @endcan
                                @can('modules.edit')<button type="button" @click="editing = !editing" class="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:text-white">Edit</button>@endcan
                                @can('modules.delete')
                                    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.destroy'), $module) }}" onsubmit="return confirm('Delete this module, its chapters, and all materials?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        @can('modules.edit')
                            <form x-show="editing" x-cloak method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.update'), $module) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                                @csrf @method('PUT')
                                <x-form.input :id="'module-title-'.$module->id" name="title" label="Module title" :value="$module->title" required />
                                <x-form.input :id="'module-description-'.$module->id" name="description" label="Description" :value="$module->description" />
                                <button class="mt-7 h-11 rounded-lg bg-brand-500 px-4 text-sm text-white">Save</button>
                            </form>
                        @endcan

                        <div class="mt-5 space-y-4">
                            @forelse($module->chapters as $chapter)
                                <section id="chapter-{{ $chapter->id }}" class="rounded-xl bg-gray-50 p-4 dark:bg-white/[0.02]" x-data="{ editingChapter: false }">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-gray-500">Chapter {{ $loop->iteration }}</p>
                                            <h4 class="font-medium text-gray-800 dark:text-white">{{ $chapter->title }}</h4>
                                            @if($chapter->description)<p class="text-sm text-gray-500">{{ $chapter->description }}</p>@endif
                                        </div>
                                        <div class="flex gap-1">
                                            @can('chapters.reorder')
                                                @foreach(['up' => '&uarr;', 'down' => '&darr;'] as $direction => $symbol)
                                                    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.move'), $chapter) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="direction" value="{{ $direction }}">
                                                        <button class="rounded border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" title="Move chapter {{ $direction }}">{!! $symbol !!}</button>
                                                    </form>
                                                @endforeach
                                            @endcan
                                            @can('chapters.edit')<button type="button" @click="editingChapter = !editingChapter" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">Edit</button>@endcan
                                            @can('chapters.delete')
                                                <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.destroy'), $chapter) }}" onsubmit="return confirm('Delete this empty chapter?')">
                                                    @csrf @method('DELETE')
                                                    <button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>

                                    @can('chapters.edit')
                                        <form x-show="editingChapter" x-cloak method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.update'), $chapter) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                                            @csrf @method('PUT')
                                            <x-form.input :id="'chapter-title-'.$chapter->id" name="title" label="Chapter title" :value="$chapter->title" required />
                                            <x-form.input :id="'chapter-description-'.$chapter->id" name="description" label="Description" :value="$chapter->description" />
                                            <button class="mt-7 h-11 rounded-lg bg-brand-500 px-4 text-sm text-white">Save</button>
                                        </form>
                                    @endcan

                                    <div class="mt-3 divide-y divide-gray-200 dark:divide-gray-800">
                                        @forelse($chapter->materials as $material)
                                            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-xs font-bold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ Str::substr($material->type->value, 0, 3) }}</span>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $material->title }}</p>
                                                        <p class="text-xs text-gray-500">{{ $material->type->label() }} · {{ $material->is_required ? 'Required' : 'Optional' }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex gap-1">
                                                    @can('materials.reorder')
                                                        @foreach(['up' => '&uarr;', 'down' => '&darr;'] as $direction => $symbol)
                                                            <form method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.move'), $material) }}">
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="direction" value="{{ $direction }}">
                                                                <button class="rounded border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">{!! $symbol !!}</button>
                                                            </form>
                                                        @endforeach
                                                    @endcan
                                                    @can('materials.edit')<a href="{{ route(\App\Support\PortalRoute::name('learning-materials.edit'), $material) }}" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">Edit</a>@endcan
                                                    @can('materials.delete')
                                                        <form method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.destroy'), $material) }}" onsubmit="return confirm('Delete this material?')">
                                                            @csrf @method('DELETE')
                                                            <button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">Delete</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        @empty
                                            <p class="py-4 text-sm text-gray-500">No learning materials in this chapter.</p>
                                        @endforelse
                                    </div>

                                    @can('materials.create')
                                        <a href="{{ route(\App\Support\PortalRoute::name('learning-materials.create'), $chapter) }}" class="mt-2 inline-flex text-sm font-medium text-brand-500">+ Add learning material</a>
                                    @endcan
                                </section>
                            @empty
                                <p class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700">No chapters yet.</p>
                            @endforelse

                            @can('chapters.create')
                                <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-chapters.store'), $module) }}" class="grid gap-3 rounded-xl border border-dashed border-gray-300 p-4 sm:grid-cols-[1fr_1fr_auto] dark:border-gray-700">
                                    @csrf
                                    <x-form.input :id="'new-chapter-title-'.$module->id" name="title" label="New chapter title" required />
                                    <x-form.input :id="'new-chapter-description-'.$module->id" name="description" label="Description" />
                                    <button class="mt-7 h-11 rounded-lg bg-brand-500 px-4 text-sm text-white">Add chapter</button>
                                </form>
                            @endcan
                        </div>
                    </section>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">No modules yet. Add the first module below.</p>
                @endforelse

                @can('modules.create')
                    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.store'), $course) }}" class="grid gap-3 rounded-xl border border-dashed border-gray-300 p-4 sm:grid-cols-[1fr_1fr_auto] dark:border-gray-700">
                        @csrf
                        <x-form.input name="title" label="New module title" required />
                        <x-form.input name="description" label="Description" />
                        <button class="mt-7 h-11 rounded-lg bg-brand-500 px-4 text-sm text-white">Add module</button>
                    </form>
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
