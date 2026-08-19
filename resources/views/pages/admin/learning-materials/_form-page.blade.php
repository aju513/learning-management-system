@php
    $materialTypes = collect([
        \App\Enums\MaterialType::Article,
        \App\Enums\MaterialType::Video,
        \App\Enums\MaterialType::File,
        \App\Enums\MaterialType::CourseAssessment,
    ]);
    $fileTypes = ['pdf' => 'PDF', 'docx' => 'DOCX', 'pptx' => 'PPTX'];
    if ($material->file_type === 'legacy') {
        $fileTypes['legacy'] = 'Legacy file (replace to change type)';
    }
    $initial = [
        'title' => old('title', $material->title ?? ''),
        'type' => old('type', $material->type?->value ?? 'article'),
        'initialType' => $material->type?->value ?? 'article',
        'description' => old('description', $material->description ?? ''),
        'duration' => old('duration_minutes', $material->duration_minutes ?? 0),
        'passingPercentage' => old('passing_percentage', $material->courseAssessment?->passing_percentage ?? 60),
        'isRequired' => (bool) old('is_required', $material->is_required ?? true),
        'content' => old('content', $material->content ?? ''),
        'videoUrl' => old('video_url', $material->video_url ?? ''),
        'videoSource' => old('video_source', filled($material->video_url) ? 'url' : 'upload'),
        'externalUrl' => old('external_url', $material->external_url ?? ''),
        'fileType' => old('file_type', $material->file_type ?? ''),
        'currentFileName' => $material->original_filename,
    ];
    $cancelUrl = route(\App\Support\PortalRoute::name('courses.show'), $chapter->module->course).'#chapter-'.$chapter->id;
    $imageUploadUrl = $material->exists
        ? route(\App\Support\PortalRoute::name('learning-material-images.store-material'), $material)
        : route(\App\Support\PortalRoute::name('learning-material-images.store-chapter'), $chapter);
@endphp

<x-common.page-breadcrumb :pageTitle="$material->exists ? 'Edit Learning Material' : 'Add Learning Material'"><x-slot:actions>
    <a href="{{ $cancelUrl }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a>
    @if($material->exists && $material->type === \App\Enums\MaterialType::CourseAssessment && $material->courseAssessment)
        <a href="{{ route(\App\Support\PortalRoute::name('course-assessments.show'), $material->courseAssessment) }}" class="rounded-lg border border-brand-300 bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300">Set up questions</a>
    @endif
    <button type="submit" form="learning-material-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button>
</x-slot:actions></x-common.page-breadcrumb>

<div
    x-data="learningMaterialEditor(@js($initial))"
    @editor-content-changed="content = $event.detail.html"
    @file-selection-changed="selectedFileName = $event.detail.name; selectedFilePreview = $event.detail.preview || null"
    class="space-y-6"
>
    <div>
        <button type="button" @click="$dispatch('open-material-preview')" class="group flex w-full items-center justify-between rounded-2xl border-2 border-dashed border-brand-300 bg-brand-50/60 px-5 py-4 text-left text-brand-700 transition hover:border-brand-500 hover:bg-brand-50 animate-pulse dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300 dark:hover:border-brand-400">
            <span class="flex items-center gap-3"><i class="bi bi-eye text-xl" aria-hidden="true"></i><span><span class="block text-sm font-semibold">Preview material</span><span class="block text-xs text-brand-600/80 dark:text-brand-300/80">See the trainee view before saving.</span></span></span>
            <i class="bi bi-box-arrow-up-right text-lg" aria-hidden="true"></i>
        </button>

        <x-ui.modal @open-material-preview.window="open = true" :isOpen="false" class="max-w-[900px]">
            <x-common.component-card title="Trainee preview" desc="This preview is read-only and does not submit links, files, or tests.">
                <article class="space-y-5">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <x-ui.badge><span x-text="typeLabel"></span></x-ui.badge>
                            <x-ui.badge color="primary"><span x-text="isRequired ? 'Required' : 'Optional'"></span></x-ui.badge>
                            <span class="text-xs text-gray-500" x-show="Number(duration) > 0" x-text="`${duration} min`"></span>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white" x-text="title || 'Untitled learning material'"></h2>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-500" x-show="description" x-text="description"></p>
                    </div>

                    <div x-show="content" x-cloak class="prose max-w-none text-gray-700 dark:text-gray-300" x-html="safeContentHtml"></div>
                    <div x-show="type === 'video' && videoPreviewUrl" x-cloak class="space-y-3">
                        <template x-if="videoEmbedUrl">
                            <div class="aspect-video overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                <iframe :src="videoEmbedUrl" title="Video preview" class="h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        </template>
                        <template x-if="!videoEmbedUrl">
                            <video controls class="w-full rounded-xl border border-gray-200 dark:border-gray-700" :src="videoPreviewUrl"></video>
                        </template>
                        <p class="break-all text-sm text-gray-500" x-text="videoUrl || previewFileName"></p>
                    </div>
                    <p x-show="type === 'video' && !videoPreviewUrl" x-cloak class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">Add a video URL or file to preview it here.</p>

                    <div x-show="type === 'course_assessment'" x-cloak class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]">
                        <p class="font-medium text-gray-800 dark:text-white">Course assessment</p>
                        <p class="mt-2 text-sm text-gray-500">Multiple-choice questions are managed after saving this material.</p>
                        <p class="mt-1 text-sm text-gray-500">Passing score: <span x-text="`${passingPercentage}%`"></span></p>
                    </div>

                    <div x-show="type === 'file'" x-cloak class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700">
                        <p class="font-medium text-gray-800 dark:text-white" x-text="previewFileName || 'No file selected'"></p>
                        <p class="mt-2 text-sm text-gray-500" x-text="fileType ? fileType.toUpperCase() + ' download' : 'Select a file type.'"></p>
                    </div>
                </article>
            </x-common.component-card>
        </x-ui.modal>
    </div>

    <x-common.component-card :title="$material->exists ? 'Material details' : 'New material'" :desc="$chapter->module->title.' / '.$chapter->title">
        <form id="learning-material-form" method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" @submit="syncContentBeforeSubmit($event)">
            @csrf
            @if($method !== 'POST') @method($method) @endif

            <div class="flex justify-end">
                <x-form.toggle name="is_required" label="Required for completion" :checked="$material->is_required ?? true" @change="isRequired = $event.target.checked" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="title" label="Material title" :value="$material->title" x-model="title" required />
                <x-form.input name="duration_minutes" label="Duration (minutes)" type="number" min="0" :value="$material->duration_minutes ?? 0" x-model="duration" />
            </div>

            <x-form.textarea name="description" label="Description" :value="$material->description" rows="3" x-model="description" />

            <div>
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Material type <span class="text-error-500">*</span></p>
                <input type="hidden" name="type" x-model="type">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($materialTypes as $materialType)
                        <button type="button" @click="type = '{{ $materialType->value }}'" :class="type === '{{ $materialType->value }}' ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-600 hover:border-brand-300 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300'" class="rounded-xl border p-4 text-left transition-colors">
                            <i class="bi {{ match($materialType) { \App\Enums\MaterialType::Article => 'bi-file-earmark-text', \App\Enums\MaterialType::Video => 'bi-camera-video', \App\Enums\MaterialType::File => 'bi-file-earmark-arrow-up', \App\Enums\MaterialType::CourseAssessment => 'bi-clipboard-check' } }} mb-2 text-xl" aria-hidden="true"></i>
                            <span class="block text-sm font-semibold">{{ $materialType->label() }}</span>
                        </button>
                    @endforeach
                </div>
                @error('type')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror
            </div>

            <div x-show="type === 'video'" x-cloak class="space-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="font-semibold text-gray-800 dark:text-white">Video source</h3>
                <input type="hidden" name="video_source" x-model="videoSource">
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="videoSource = 'url'" :class="videoSource === 'url' ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300'" class="rounded-lg border px-4 py-3 text-left text-sm font-medium">Use video URL</button>
                    <button type="button" @click="videoSource = 'upload'" :class="videoSource === 'upload' ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300'" class="rounded-lg border px-4 py-3 text-left text-sm font-medium">Upload video</button>
                </div>
                <div x-show="videoSource === 'url'" x-cloak>
                    <x-form.input name="video_url" label="Video URL" type="url" :value="$material->video_url" x-model="videoUrl" help="Use a YouTube, Vimeo, or other HTTPS video URL." />
                </div>
                <div x-show="videoSource === 'upload'" x-cloak>
                    <x-form.file-upload name="file" label="Upload video" accept=".mp4,.webm" :max-size="104857600" :help="$material->original_filename ? 'Current file: '.$material->original_filename : 'Allowed formats: MP4 and WebM.'" />
                </div>
            </div>

            <div x-show="type === 'file'" x-cloak class="space-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="font-semibold text-gray-800 dark:text-white">Learning file</h3>
                <x-form.select name="file_type" label="File type" :options="$fileTypes" :value="$material->file_type" x-model="fileType" />
                <x-form.file-upload name="file" label="Upload file" accept=".pdf,.docx,.pptx" :max-size="104857600" :help="$material->original_filename ? 'Current file: '.$material->original_filename : 'Allowed formats: PDF, DOCX, and PPTX.'" />
            </div>

            <div x-show="type === 'course_assessment'" x-cloak class="space-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="font-semibold text-gray-800 dark:text-white">Course assessment settings</h3>
                <x-form.input name="passing_percentage" label="Passing score (%)" type="number" min="0" max="100" step="0.01" x-model="passingPercentage" required />
                <p class="text-sm text-gray-500">Retakes are unlimited. You can add the multiple-choice questions after saving.</p>
            </div>

            @if($material->exists && $material->type === \App\Enums\MaterialType::Link)
                <input type="hidden" name="external_url" value="{{ $material->external_url }}">
            @endif

            <div>
                <x-form.editor name="content" label="Article content / additional notes" :value="$material->content" :image-upload-url="$imageUploadUrl" />
                <p class="mt-1 text-xs text-gray-500">Article materials require content. For other types, use this field for explanations, instructions, or study notes.</p>
            </div>

        </form>
    </x-common.component-card>
</div>
