@php
    $materialTypes = collect(\App\Enums\MaterialType::cases())
        ->filter(fn ($type) => $type !== \App\Enums\MaterialType::Assessment || $material->type === \App\Enums\MaterialType::Assessment)
        ->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all();
    $assessmentOptions = $attachableAssessments->map(fn ($assessment) => [
        'id' => $assessment->id,
        'title' => $assessment->title,
        'duration' => $assessment->duration_minutes,
        'passingPercentage' => $assessment->passing_percentage,
    ])->values();
    $initial = [
        'title' => old('title', $material->title ?? ''),
        'type' => old('type', $material->type?->value ?? 'article'),
        'initialType' => $material->type?->value ?? 'article',
        'description' => old('description', $material->description ?? ''),
        'duration' => old('duration_minutes', $material->duration_minutes ?? 0),
        'isRequired' => (bool) old('is_required', $material->is_required ?? true),
        'content' => old('content', $material->content ?? ''),
        'externalUrl' => old('external_url', $material->external_url ?? ''),
        'assessmentId' => old('assessment_id', $material->assessment_id ?? ''),
        'currentFileName' => $material->original_filename,
    ];
    $cancelUrl = route(\App\Support\PortalRoute::name('courses.show'), $chapter->module->course).'#chapter-'.$chapter->id;
@endphp

<x-common.page-breadcrumb :pageTitle="$material->exists ? 'Edit Learning Material' : 'Add Learning Material'" />

<div
    x-data="learningMaterialEditor(@js($initial), @js($assessmentOptions))"
    @editor-content-changed="content = $event.detail.html"
    @file-selection-changed="selectedFileName = $event.detail.name"
    class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_420px]"
>
    <x-common.component-card :title="$material->exists ? 'Material details' : 'New material'" :desc="$chapter->module->title.' / '.$chapter->title">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if($method !== 'POST') @method($method) @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="title" label="Title" :value="$material->title" x-model="title" required />
                <x-form.select name="type" label="Material type" :options="$materialTypes" :value="$material->type?->value" x-model="type" required />
                <x-form.input name="duration_minutes" label="Duration (minutes)" type="number" min="0" :value="$material->duration_minutes ?? 0" x-model="duration" />
                <x-form.toggle name="is_required" label="Required for completion" :checked="$material->is_required ?? true" @change="isRequired = $event.target.checked" />
            </div>

            <x-form.textarea name="description" label="Description" :value="$material->description" rows="3" x-model="description" />

            <div x-show="type === 'article'" x-cloak>
                <x-form.editor name="content" label="Article content" :value="$material->content" />
            </div>

            <div x-show="['video', 'external_link'].includes(type)" x-cloak>
                <x-form.input name="external_url" label="Video / external URL" type="url" :value="$material->external_url" x-model="externalUrl" help="Videos may use a URL or an uploaded video file." />
            </div>

            <div x-show="['video', 'pdf', 'ppt', 'pptx', 'doc', 'docx', 'downloadable_file'].includes(type)" x-cloak>
                <x-form.file-upload name="file" :label="$material->file_path ? 'Replace file' : 'Learning file'" accept=".pdf,.ppt,.pptx,.doc,.docx,.zip,.mp4,.webm" :max-size="104857600" :help="$material->original_filename ? 'Current file: '.$material->original_filename : null" />
            </div>

            <div x-show="type === 'assessment'" x-cloak>
                <x-form.select name="assessment_id" label="Attached assessment" :options="$attachableAssessments->pluck('title', 'id')" :value="$material->assessment_id" x-model="assessmentId" placeholder="Select an assessment" />
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ $cancelUrl }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a>
                <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button>
            </div>
        </form>
    </x-common.component-card>

    <aside class="xl:sticky xl:top-24">
        <x-common.component-card title="Trainee preview" desc="This preview is read-only and does not open links, files, or tests.">
            <article class="space-y-5">
                <div>
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <x-ui.badge><span x-text="type.replace('_', ' ')"></span></x-ui.badge>
                        <x-ui.badge color="primary"><span x-text="isRequired ? 'Required' : 'Optional'"></span></x-ui.badge>
                        <span class="text-xs text-gray-500" x-show="Number(duration) > 0" x-text="`${duration} min`"></span>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white" x-text="title || 'Untitled learning material'"></h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-500" x-show="description" x-text="description"></p>
                </div>

                <div x-show="type === 'article'" x-cloak>
                    <div x-show="safeArticleHtml" class="prose max-w-none text-gray-700 dark:text-gray-300" x-html="safeArticleHtml"></div>
                    <p x-show="!safeArticleHtml" class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">Article content will appear here.</p>
                </div>

                <div x-show="type === 'video'" x-cloak class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700">
                    <p class="font-medium text-gray-800 dark:text-white">Video resource</p>
                    <p class="mt-2 break-all text-sm text-gray-500" x-text="previewFileName || externalUrl || 'Add a video URL or file to preview its details.'"></p>
                </div>

                <div x-show="type === 'external_link'" x-cloak class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]">
                    <p class="font-medium text-gray-800 dark:text-white">External resource</p>
                    <p class="mt-2 break-all text-sm text-brand-500" x-text="externalUrl || 'Enter a URL to preview it.'"></p>
                </div>

                <div x-show="type === 'assessment'" x-cloak class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]">
                    <template x-if="selectedAssessment">
                        <div><h3 class="font-semibold text-gray-800 dark:text-white" x-text="selectedAssessment.title"></h3><p class="mt-1 text-sm text-gray-500" x-text="`Pass score ${selectedAssessment.passingPercentage}% · ${selectedAssessment.duration} minutes`"></p></div>
                    </template>
                    <p x-show="!selectedAssessment" class="text-sm text-gray-500">Select an assessment to preview it.</p>
                </div>

                <div x-show="['pdf', 'ppt', 'pptx', 'doc', 'docx', 'downloadable_file'].includes(type)" x-cloak class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700">
                    <p class="font-medium text-gray-800 dark:text-white" x-text="previewFileName || 'No file selected'"></p>
                    <p class="mt-2 text-sm text-gray-500">The trainee will receive a download action here.</p>
                </div>
            </article>
        </x-common.component-card>
    </aside>
</div>
