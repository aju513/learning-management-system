@props([
    'name',
    'label' => null,
    'id' => null,
    'accept' => null,
    'multiple' => false,
    'maxFiles' => null,
    'maxSize' => null,
    'placeholder' => 'Drag and drop files here or browse',
    'help' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
@endphp

<x-form.field :name="$name" :label="$label" :id="$id" :required="$required" :error="$error" :help="$help">
    <div
        x-data="{
            isDragging: false,
            files: [],
            errors: [],
            accept: @js($accept),
            multiple: @js((bool) $multiple),
            maxFiles: @js($maxFiles),
            maxSize: @js($maxSize),
            handleDrop(event) {
                this.isDragging = false;
                if (!{{ $disabled ? 'true' : 'false' }}) {
                    this.handleFiles(event.dataTransfer.files);
                }
            },
            handleFiles(fileList) {
                this.errors = [];
                const incoming = Array.from(fileList);
                const validFiles = incoming.filter(file => {
                    if (this.maxSize && file.size > this.maxSize) {
                        this.errors.push(`${file.name} exceeds the ${this.formatSize(this.maxSize)} limit.`);
                        return false;
                    }
                    if (this.accept && !this.matchesAccept(file)) {
                        this.errors.push(`${file.name} is not an accepted file type.`);
                        return false;
                    }
                    return true;
                });

                const nextFiles = this.multiple ? [...this.files, ...validFiles] : validFiles.slice(0, 1);
                const uniqueFiles = nextFiles.filter((file, index, list) => list.findIndex(candidate => candidate.name === file.name && candidate.size === file.size && candidate.lastModified === file.lastModified) === index);
                const limit = this.maxFiles || (this.multiple ? uniqueFiles.length : 1);

                if (uniqueFiles.length > limit) {
                    this.errors.push(`You can select up to ${limit} file${limit === 1 ? '' : 's'}.`);
                }

                this.files = uniqueFiles.slice(0, limit).map(file => ({
                    file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
                }));
                this.syncInput();
                this.$dispatch('file-selection-changed', { name: this.files[0]?.name || null });
            },
            matchesAccept(file) {
                return this.accept.split(',').map(item => item.trim().toLowerCase()).filter(Boolean).some(rule => {
                    if (rule.startsWith('.')) return file.name.toLowerCase().endsWith(rule);
                    if (rule.endsWith('/*')) return file.type.toLowerCase().startsWith(rule.slice(0, -1));
                    return file.type.toLowerCase() === rule;
                });
            },
            syncInput() {
                if (!window.DataTransfer) return;
                const dataTransfer = new DataTransfer();
                this.files.forEach(entry => dataTransfer.items.add(entry.file));
                this.$refs.fileInput.files = dataTransfer.files;
            },
            removeFile(index) {
                const [removed] = this.files.splice(index, 1);
                if (removed?.preview) URL.revokeObjectURL(removed.preview);
                this.syncInput();
                this.$dispatch('file-selection-changed', { name: this.files[0]?.name || null });
            },
            formatSize(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                const unit = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                return `${(bytes / (1024 ** unit)).toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
            }
        }"
        class="space-y-3"
    >
        <input
            x-ref="fileInput"
            id="{{ $id }}"
            name="{{ $name }}"
            type="file"
            accept="{{ $accept }}"
            @if($multiple) multiple @endif
            @required($required)
            @disabled($disabled)
            @change="handleFiles($event.target.files)"
            {{ $attributes->except(['class']) }}
            class="sr-only"
        >

        <div
            role="button"
            tabindex="{{ $disabled ? '-1' : '0' }}"
            @if(!$disabled)
                @click="$refs.fileInput.click()"
                @keydown.enter.prevent="$refs.fileInput.click()"
                @keydown.space.prevent="$refs.fileInput.click()"
                @drop.prevent="handleDrop($event)"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
            @endif
            :class="isDragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900'"
            class="rounded-xl border border-dashed p-7 text-center transition-colors {{ $disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:border-brand-500 dark:hover:border-brand-500' }}"
        >
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <svg class="h-7 w-7 fill-current" viewBox="0 0 29 28" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 3.9a.75.75 0 0 1 .53.22l5.38 5.38a.75.75 0 1 1-1.06 1.06l-4.1-4.11v12.2a.75.75 0 0 1-1.5 0V6.48l-4.12 4.11a.75.75 0 0 1-1.06-1.06l5.4-5.4a.75.75 0 0 1 .53-.22ZM5.9 17.9a.75.75 0 0 1 .75.75v3.18c0 .41.34.75.75.75h15.67c.41 0 .75-.34.75-.75v-3.18a.75.75 0 0 1 1.5 0v3.18a2.25 2.25 0 0 1-2.25 2.25H7.4a2.25 2.25 0 0 1-2.25-2.25v-3.18a.75.75 0 0 1 .75-.75Z" /></svg>
            </div>
            <p class="mt-4 font-semibold text-gray-800 dark:text-white/90" x-text="isDragging ? 'Drop files here' : '{{ $placeholder }}'"></p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $accept ? 'Accepted: '.$accept : 'Choose a file from your device.' }}{{ $maxSize ? ' Maximum size: '.number_format($maxSize / 1048576, 1).' MB.' : '' }}</p>
        </div>

        <ul x-show="errors.length" x-cloak class="space-y-1 text-xs text-error-500" role="alert">
            <template x-for="message in errors" :key="message"><li x-text="message"></li></template>
        </ul>

        <ul x-show="files.length" x-cloak class="space-y-2">
            <template x-for="(entry, index) in files" :key="`${entry.name}-${entry.size}-${index}`">
                <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex min-w-0 items-center gap-3">
                        <template x-if="entry.preview"><img :src="entry.preview" :alt="entry.name" class="h-10 w-10 rounded object-cover"></template>
                        <template x-if="!entry.preview"><span class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs font-medium text-gray-500 dark:bg-gray-800">FILE</span></template>
                        <span class="min-w-0"><span class="block truncate text-sm font-medium text-gray-700 dark:text-gray-200" x-text="entry.name"></span><span class="block text-xs text-gray-500" x-text="formatSize(entry.size)"></span></span>
                    </div>
                    <button type="button" @click.stop="removeFile(index)" class="shrink-0 text-gray-400 hover:text-error-500" aria-label="Remove file">&times;</button>
                </li>
            </template>
        </ul>
    </div>
</x-form.field>
