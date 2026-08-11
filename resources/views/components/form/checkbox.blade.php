@props([
    'name',
    'value' => '1',
    'label' => null,
    'description' => null,
    'id' => null,
    'checked' => false,
    'disabled' => false,
    'error' => null,
    'containerClass' => '',
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name) . '-' . substr(md5((string) $value), 0, 6);
    $oldName = preg_replace('/\[\]$/', '', $name);
    $currentValue = old($oldName, $checked);
    $isChecked = is_array($currentValue)
        ? in_array((string) $value, array_map('strval', $currentValue), true)
        : (bool) $currentValue;
@endphp

<div class="space-y-1.5">
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 text-sm text-gray-700 select-none dark:text-gray-300 {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }} {{ $containerClass }}">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="{{ $value }}"
            @checked($isChecked)
            @disabled($disabled)
            class="mt-0.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700"
            {{ $attributes }}
        >
        <span>
            @if($label)<span class="block font-medium">{{ $label }}</span>@endif
            @if($description)<span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $description }}</span>@endif
        </span>
    </label>
    @if($error ?: ($oldName ? $errors->first($oldName) : null))
        <p class="text-xs text-error-500" role="alert">{{ $error ?: $errors->first($oldName) }}</p>
    @endif
</div>
