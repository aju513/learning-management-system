@props([
    'name',
    'label',
    'id' => null,
    'checked' => false,
    'onValue' => '1',
    'offValue' => '0',
    'help' => null,
    'error' => null,
    'disabled' => false,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
    $currentValue = old($name, $checked);
@endphp

<x-form.field :name="$name" :id="$id" :error="$error" :help="$help">
    <div x-data="{ checked: @js((bool) $currentValue) }">
        @unless($disabled)<input type="hidden" name="{{ $name }}" value="{{ $offValue }}">@endunless
        <label for="{{ $id }}" class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400 {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }}">
            <span class="relative shrink-0">
                <input
                    id="{{ $id }}"
                    name="{{ $name }}"
                    type="checkbox"
                    value="{{ $onValue }}"
                    x-model="checked"
                    @disabled($disabled)
                    class="sr-only"
                    {{ $attributes }}
                >
                <span class="block h-6 w-11 rounded-full transition" :class="checked ? 'bg-brand-500' : 'bg-gray-200 dark:bg-white/10'" aria-hidden="true"></span>
                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-theme-sm transition" :class="checked ? 'translate-x-full' : 'translate-x-0'" aria-hidden="true"></span>
            </span>
            {{ $label }}
        </label>
    </div>
</x-form.field>
