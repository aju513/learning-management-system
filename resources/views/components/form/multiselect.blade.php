@props([
    'name',
    'label' => null,
    'id' => null,
    'options' => [],
    'value' => [],
    'placeholder' => 'Select options',
    'help' => null,
    'error' => null,
    'disabled' => false,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
    $oldName = preg_replace('/\[\]$/', '', $name);
    $currentValue = old($oldName, $value);
    $selectedValues = is_array($currentValue) ? array_map('strval', $currentValue) : ($currentValue === null || $currentValue === '' ? [] : [(string) $currentValue]);
    $normalizedOptions = [];

    foreach ($options as $optionKey => $option) {
        if (is_array($option)) {
            $optionValue = $option['value'] ?? $option['id'] ?? $optionKey;
            $optionLabel = $option['label'] ?? $option['name'] ?? $optionValue;
        } elseif (is_object($option)) {
            $optionValue = $option->value ?? $option->id ?? $optionKey;
            $optionLabel = $option->label ?? $option->name ?? $optionValue;
        } else {
            $optionValue = $optionKey;
            $optionLabel = $option;
        }

        $normalizedOptions[] = ['value' => (string) $optionValue, 'label' => (string) (is_array($option) || is_object($option) ? $optionLabel : __($optionLabel))];
    }
@endphp

<x-form.field :name="$oldName" :label="$label" :id="$id" :error="$error" :help="$help">
    <div
        x-data="{
            open: false,
            search: '',
            selected: @js($selectedValues),
            options: @js($normalizedOptions),
            get filteredOptions() {
                return this.options.filter(option => option.label.toLowerCase().includes(this.search.toLowerCase()));
            },
            toggle(value) {
                value = String(value);
                this.selected = this.selected.includes(value)
                    ? this.selected.filter(selectedValue => selectedValue !== value)
                    : [...this.selected, value];
            },
            remove(value) {
                this.selected = this.selected.filter(selectedValue => selectedValue !== String(value));
            },
            labelFor(value) {
                return this.options.find(option => option.value === String(value))?.label ?? value;
            }
        }"
        @click.outside="open = false"
        class="relative"
    >
        <template x-for="value in selected" :key="`{{ $id }}-${value}`">
            <input type="hidden" name="{{ $name }}" :value="value">
        </template>

        <div id="{{ $id }}" @if(!$disabled) @click="open = !open" @keydown.enter.prevent="open = !open" @endif role="button" tabindex="{{ $disabled ? '-1' : '0' }}" aria-haspopup="listbox" aria-disabled="{{ $disabled ? 'true' : 'false' }}" class="flex min-h-11 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-left text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 {{ $disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
            <span class="flex flex-1 flex-wrap items-center gap-1.5">
                <template x-for="value in selected" :key="`{{ $id }}-tag-${value}`">
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <span x-text="labelFor(value)"></span>
                        <button type="button" @click.stop="remove(value)" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white" aria-label="Remove option">&times;</button>
                    </span>
                </template>
            <span x-show="selected.length === 0" class="text-gray-500 dark:text-gray-400">{{ __($placeholder) }}</span>
            </span>
            <svg class="h-5 w-5 shrink-0 text-gray-500 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4.8 7.4 5.2 5.2 5.2-5.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </div>

        <div x-show="open" x-transition class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900" style="display: none">
            <div class="border-b border-gray-100 p-2 dark:border-gray-800">
                <input x-model="search" type="search" placeholder="{{ __('Search options...') }}" class="h-9 w-full rounded-md border border-gray-200 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
            </div>
            <div class="max-h-56 overflow-y-auto p-1">
                <template x-for="option in filteredOptions" :key="option.value">
                    <button type="button" @click="toggle(option.value)" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                        <span x-text="option.label" class="text-gray-700 dark:text-gray-200"></span>
                        <span x-show="selected.includes(option.value)" class="text-brand-500" aria-hidden="true">✓</span>
                    </button>
                </template>
                <p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">{{ __('No options found.') }}</p>
            </div>
        </div>
    </div>
</x-form.field>
