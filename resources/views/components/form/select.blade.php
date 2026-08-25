@props([
    'name',
    'label' => null,
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'help' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
    $currentValue = old($name, $value);
    $normalizedOptions = [];

    foreach ($options as $optionKey => $option) {
        if (is_array($option)) {
            $optionValue = $option['value'] ?? $option['id'] ?? $optionKey;
            $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? $optionValue;
        } elseif (is_object($option)) {
            $optionValue = $option->value ?? $option->id ?? $optionKey;
            $optionLabel = $option->label ?? $option->name ?? $option->title ?? $optionValue;
        } else {
            $optionValue = $optionKey;
            $optionLabel = $option;
        }

        $normalizedOptions[] = ['value' => $optionValue, 'label' => is_array($option) || is_object($option) ? $optionLabel : __($optionLabel)];
    }

    $selectClasses = 'h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800';
@endphp

<x-form.field :name="$name" :label="$label" :id="$id" :required="$required" :error="$error" :help="$help">
    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @required($required)
            @disabled($disabled)
            {{ $attributes->merge(['class' => $selectClasses]) }}
        >
            @if($placeholder)
                <option value="">{{ __($placeholder) }}</option>
            @endif
            @foreach($normalizedOptions as $option)
                <option value="{{ $option['value'] }}" @selected((string) $currentValue === (string) $option['value'])>{{ __($option['label']) }}</option>
            @endforeach
        </select>
        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-700 dark:text-gray-400" aria-hidden="true">
            <svg class="h-5 w-5 stroke-current" viewBox="0 0 20 20" fill="none"><path d="m4.8 7.4 5.2 5.2 5.2-5.2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </span>
    </div>
</x-form.field>
