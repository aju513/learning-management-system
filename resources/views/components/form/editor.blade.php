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
    'imageUploadUrl' => null,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
    $currentValue = old($name, $value);
@endphp

<x-form.field :name="$name" :label="$label" :id="$id" :required="$required" :error="$error" :help="$help">
    <div
        data-quill-editor
        data-input-id="{{ $id }}"
        data-placeholder="{{ $placeholder }}"
        data-disabled="{{ $disabled ? 'true' : 'false' }}"
        data-image-upload-url="{{ $imageUploadUrl }}"
        class="quill-editor overflow-hidden rounded-lg border border-gray-300 shadow-theme-xs dark:border-gray-700"
    >
        <div id="{{ $id }}-toolbar" class="ql-toolbar ql-snow">
            <span class="ql-formats">
                <select class="ql-header" aria-label="Heading">
                    <option value="1"></option>
                    <option value="2"></option>
                    <option selected></option>
                </select>
            </span>
            <span class="ql-formats">
                <button class="ql-bold" type="button" aria-label="Bold"></button>
                <button class="ql-italic" type="button" aria-label="Italic"></button>
                <button class="ql-underline" type="button" aria-label="Underline"></button>
                <button class="ql-blockquote" type="button" aria-label="Blockquote"></button>
            </span>
            <span class="ql-formats">
                <button class="ql-list" value="ordered" type="button" aria-label="Numbered list"></button>
                <button class="ql-list" value="bullet" type="button" aria-label="Bulleted list"></button>
                <button class="ql-link" type="button" aria-label="Insert link"></button>
                @if($imageUploadUrl)
                    <button class="ql-image" type="button" aria-label="Upload image"></button>
                @endif
                <button class="ql-clean" type="button" aria-label="Remove formatting"></button>
            </span>
        </div>
        <div id="{{ $id }}-editor" class="ql-container ql-snow min-h-36 text-sm" data-placeholder="{{ $placeholder }}"></div>
        <textarea id="{{ $id }}" name="{{ $name }}" class="hidden" @required($required) @disabled($disabled)>{{ $currentValue }}</textarea>
        @if($imageUploadUrl)
            <p data-quill-upload-error class="hidden px-3 pb-3 text-sm text-error-500" role="alert"></p>
        @endif
    </div>
</x-form.field>
