@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$course->title">
    <x-slot:actions>
        @can('courses.edit')<a href="{{ route(\App\Support\PortalRoute::name('courses.edit'), $course) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Edit details</a>@endcan
        @can('courses.publish')
            <form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.status'), $course) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $course->status->value === 'published' ? 'archived' : 'published' }}"><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $course->status->value === 'published' ? 'Archive' : 'Publish' }}</button></form>
        @endcan
        @can('courses.delete')<form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.destroy'), $course) }}" onsubmit="return confirm('Delete this course and curriculum?')">@csrf @method('DELETE')<button class="rounded-lg bg-error-50 px-4 py-2.5 text-sm text-error-600">Delete</button></form>@endcan
    </x-slot:actions>
</x-common.page-breadcrumb>

<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <div class="space-y-6">
        <x-common.component-card title="Curriculum" desc="Modules and materials appear to trainees in this order.">
            @forelse($course->modules as $module)
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800" x-data="{ editing: false, adding: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="text-xs font-semibold uppercase text-brand-500">Module {{ $loop->iteration }}</p><h3 class="font-semibold text-gray-800 dark:text-white">{{ $module->title }}</h3><p class="text-sm text-gray-500">{{ $module->description }}</p></div>
                        <div class="flex gap-1">
                            @can('modules.reorder') @foreach(['up' => '↑', 'down' => '↓'] as $direction => $symbol)<form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.move'), $module) }}">@csrf @method('PATCH')<input type="hidden" name="direction" value="{{ $direction }}"><button class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:text-white" title="Move {{ $direction }}">{{ $symbol }}</button></form>@endforeach @endcan
                            @can('modules.edit')<button type="button" @click="editing = !editing" class="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:text-white">Edit</button>@endcan
                            @can('modules.delete')<form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.destroy'), $module) }}" onsubmit="return confirm('Delete this module and all its materials?')">@csrf @method('DELETE')<button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">Delete</button></form>@endcan
                        </div>
                    </div>
                    @can('modules.edit')<form x-show="editing" method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.update'), $module) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" style="display:none">@csrf @method('PUT')<x-form.input :id="'module-title-'.$module->id" name="title" label="Module title" :value="$module->title" required /><x-form.input :id="'module-description-'.$module->id" name="description" label="Description" :value="$module->description" /><button class="mt-7 h-11 rounded-lg bg-brand-500 px-4 text-sm text-white">Save</button></form>@endcan

                    <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($module->materials as $material)
                            <div class="py-3" x-data="{ editingMaterial: false }">
                                <div class="flex flex-wrap items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ Str::substr($material->type->value, 0, 3) }}</span><div><p class="text-sm font-medium text-gray-800 dark:text-white">{{ $material->title }}</p><p class="text-xs text-gray-500">{{ $material->type->label() }} · {{ $material->is_required ? 'Required' : 'Optional' }}</p></div></div>
                                <div class="flex gap-1">@can('materials.reorder') @foreach(['up' => '↑', 'down' => '↓'] as $direction => $symbol)<form method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.move'), $material) }}">@csrf @method('PATCH')<input type="hidden" name="direction" value="{{ $direction }}"><button class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:text-white">{{ $symbol }}</button></form>@endforeach @endcan @can('materials.edit')<button type="button" @click="editingMaterial = !editingMaterial" class="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:text-white">Edit</button>@endcan @can('materials.delete')<form method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.destroy'), $material) }}" onsubmit="return confirm('Delete this material?')">@csrf @method('DELETE')<button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">Delete</button></form>@endcan</div></div>
                                @can('materials.edit')<form x-show="editingMaterial" method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.update'), $material) }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-lg bg-gray-50 p-4 dark:bg-white/[0.02]" style="display:none">@csrf @method('PUT')
                                    <div class="grid gap-4 sm:grid-cols-2"><x-form.input :id="'material-title-'.$material->id" name="title" label="Title" :value="$material->title" required /><x-form.select :id="'material-type-'.$material->id" name="type" label="Type" :options="collect(\App\Enums\MaterialType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()" :value="$material->type->value" required /><x-form.input :id="'material-url-'.$material->id" name="external_url" label="Video / external URL" :value="$material->external_url" /><x-form.input :id="'material-duration-'.$material->id" name="duration_minutes" label="Duration (minutes)" type="number" min="0" :value="$material->duration_minutes" /><x-form.select :id="'material-assessment-'.$material->id" name="assessment_id" label="Attached assessment" :options="$attachableAssessments" :value="$material->assessment_id" placeholder="None" /><x-form.toggle :id="'material-required-'.$material->id" name="is_required" label="Required for completion" :checked="$material->is_required" /></div>
                                    <x-form.textarea :id="'material-description-'.$material->id" name="description" label="Description" :value="$material->description" rows="2" /><x-form.editor :id="'material-content-'.$material->id" name="content" label="Article content" :value="$material->content" /><x-form.file-upload :id="'material-file-'.$material->id" name="file" label="Replace file" accept=".pdf,.ppt,.pptx,.doc,.docx,.zip,.mp4,.webm" :max-size="104857600" />
                                    <div class="text-right"><button class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Save material</button></div>
                                </form>@endcan
                            </div>
                        @empty<p class="py-4 text-sm text-gray-500">No materials yet.</p>@endforelse
                    </div>
                    @can('materials.create')
                        <button type="button" @click="adding = !adding" class="mt-3 text-sm font-medium text-brand-500">+ Add learning material</button>
                        <form x-show="adding" method="POST" action="{{ route(\App\Support\PortalRoute::name('learning-materials.store'), $module) }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-700" style="display:none">@csrf
                            <div class="grid gap-4 sm:grid-cols-2"><x-form.input :id="'new-material-title-'.$module->id" name="title" label="Title" required /><x-form.select :id="'new-material-type-'.$module->id" name="type" label="Type" :options="collect(\App\Enums\MaterialType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()" required /><x-form.input :id="'new-material-url-'.$module->id" name="external_url" label="Video / external URL" /><x-form.input :id="'new-material-duration-'.$module->id" name="duration_minutes" label="Duration (minutes)" type="number" min="0" value="0" /><x-form.select :id="'new-material-assessment-'.$module->id" name="assessment_id" label="Attach assessment" :options="$attachableAssessments" placeholder="None" /><x-form.toggle :id="'new-material-required-'.$module->id" name="is_required" label="Required for completion" :checked="true" /></div>
                            <x-form.textarea :id="'new-material-description-'.$module->id" name="description" label="Description" rows="2" /><x-form.editor :id="'new-material-content-'.$module->id" name="content" label="Article content" /><x-form.file-upload :id="'new-material-file-'.$module->id" name="file" label="Learning file" accept=".pdf,.ppt,.pptx,.doc,.docx,.zip,.mp4,.webm" :max-size="104857600" />
                            <div class="text-right"><button class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Add material</button></div>
                        </form>
                    @endcan
                </section>
            @empty<p class="py-8 text-center text-sm text-gray-500">No modules yet. Add the first module below.</p>@endforelse
            @can('modules.create')<form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-modules.store'), $course) }}" class="grid gap-3 rounded-xl border border-dashed border-gray-300 p-4 sm:grid-cols-[1fr_1fr_auto] dark:border-gray-700">@csrf <x-form.input name="title" label="New module title" required /><x-form.input name="description" label="Description" /><button class="mt-7 h-11 rounded-lg bg-brand-500 px-4 text-sm text-white">Add module</button></form>@endcan
        </x-common.component-card>
    </div>
    <aside class="space-y-6">
        <x-common.component-card title="Course overview"><dl class="space-y-3 text-sm"><div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><x-ui.badge :color="$course->status->value === 'published' ? 'success' : 'warning'">{{ $course->status->value }}</x-ui.badge></dd></div><div class="flex justify-between"><dt class="text-gray-500">Instructor</dt><dd class="text-right text-gray-800 dark:text-white">{{ $course->instructor?->name ?? 'Unassigned' }}</dd></div><div class="flex justify-between"><dt class="text-gray-500">Category</dt><dd class="text-gray-800 dark:text-white">{{ $course->category?->name ?? 'None' }}</dd></div><div class="flex justify-between"><dt class="text-gray-500">Navigation</dt><dd class="capitalize text-gray-800 dark:text-white">{{ $course->navigation_mode->value }}</dd></div><div class="flex justify-between"><dt class="text-gray-500">Enrollments</dt><dd class="text-gray-800 dark:text-white">{{ $course->enrollments_count }}</dd></div></dl></x-common.component-card>
        <x-common.component-card title="Description"><p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $course->description ?: $course->short_description }}</p></x-common.component-card>
    </aside>
</div>
@endsection
