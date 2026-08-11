@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'Write something...',
    'help' => 'HTML is submitted; sanitize rich text on the server before storing or rendering it.',
    'error' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
    $currentValue = old($name, $value);
@endphp

<x-form.field :name="$name" :label="$label" :id="$id" :required="$required" :error="$error" :help="$help">
    <div x-data="{
        html: @js($currentValue),
        format(command) {
            document.execCommand(command, false, null);
            this.html = this.$refs.editor.innerHTML;
            this.$refs.editor.focus();
        }
    }" class="overflow-hidden rounded-lg border border-gray-300 shadow-theme-xs dark:border-gray-700">
        <div class="flex flex-wrap gap-1 border-b border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900">
            <button type="button" @click="format('bold')" class="rounded px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800" aria-label="Bold">B</button>
            <button type="button" @click="format('italic')" class="rounded px-2 py-1 text-xs italic text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800" aria-label="Italic">I</button>
            <button type="button" @click="format('insertUnorderedList')" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800" aria-label="Bulleted list">• List</button>
        </div>
        <div
            id="{{ $id }}"
            x-ref="editor"
            contenteditable="{{ $disabled ? 'false' : 'true' }}"
            role="textbox"
            aria-multiline="true"
            data-placeholder="{{ $placeholder }}"
            x-init="$refs.editor.innerHTML = html"
            @input="html = $event.target.innerHTML"
            class="min-h-36 w-full px-4 py-3 text-sm text-gray-800 outline-none empty:before:text-gray-400 empty:before:content-[attr(data-placeholder)] dark:bg-gray-900 dark:text-white/90"
        ></div>
        <textarea name="{{ $name }}" x-model="html" class="hidden" @required($required) @disabled($disabled)></textarea>
    </div>
</x-form.field>
