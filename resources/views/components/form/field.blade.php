@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'required' => false,
    'error' => null,
    'help' => null,
])

@php
    $errorName = $name ? preg_replace('/\[\]$/', '', $name) : null;
    $fieldError = $error ?: ($errorName ? $errors->first($errorName) : null);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }} @if($required)<span class="text-error-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if($fieldError)
        <p class="text-xs text-error-500" role="alert">{{ $fieldError }}</p>
    @elseif($help)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif
</div>
