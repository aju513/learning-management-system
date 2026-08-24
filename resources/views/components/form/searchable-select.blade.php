@props([
    'name',
    'label' => null,
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'searchPlaceholder' => 'Search options...',
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

        $normalizedOptions[] = ['value' => (string) $optionValue, 'label' => (string) (is_array($option) || is_object($option) ? $optionLabel : __($optionLabel))];
    }
@endphp

<x-form.field :name="$name" :label="$label" :id="$id" :required="$required" :error="$error" :help="$help">
    <div
        x-data="{
            open: false,
            search: '',
            placeholder: @js(__($placeholder)),
            selected: @js($currentValue === null ? '' : (string) $currentValue),
            options: @js($normalizedOptions),
            get filteredOptions() {
                return this.options.filter(option => option.label.toLowerCase().includes(this.search.toLowerCase()));
            },
            get selectedLabel() {
                return this.options.find(option => option.value === this.selected)?.label ?? '';
            },
            choose(value) {
                this.selected = String(value);
                this.search = '';
                this.open = false;
            },
            clear() {
                this.selected = '';
                this.search = '';
                this.open = false;
            }
        }"
        @click.outside="open = false"
        class="relative"
    >
        <input type="hidden" name="{{ $name }}" x-model="selected" @required($required)>

        <button
            type="button"
            id="{{ $id }}"
            @click="open = !open"
            @disabled($disabled)
            aria-haspopup="listbox"
            :aria-expanded="open"
            class="flex h-11 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-left text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800 {{ $disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}"
        >
            <span x-text="selectedLabel || placeholder" :class="selected ? 'text-gray-800 dark:text-white/90' : 'text-gray-400 dark:text-white/30'"></span>
            <svg class="h-5 w-5 shrink-0 text-gray-500 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4.8 7.4 5.2 5.2 5.2-5.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </button>

        <div x-show="open" x-transition x-cloak class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 p-2 dark:border-gray-800">
                <input x-model="search" type="search" placeholder="{{ $searchPlaceholder }}" @click.stop class="h-9 w-full rounded-md border border-gray-200 bg-transparent px-3 text-sm text-gray-800 outline-none focus:border-brand-300 dark:border-gray-700 dark:text-white">
            </div>
            <div class="max-h-56 overflow-y-auto p-1" role="listbox" aria-label="{{ $label ?? $name }}">
                @if($placeholder)
                    <button type="button" @click="clear()" class="flex w-full rounded-md px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-white/[0.03]">{{ __($placeholder) }}</button>
                @endif
                <template x-for="option in filteredOptions" :key="option.value">
                    <button type="button" @click="choose(option.value)" role="option" :aria-selected="selected === option.value" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                        <span x-text="option.label" class="text-gray-700 dark:text-gray-200"></span>
                        <span x-show="selected === option.value" class="text-brand-500" aria-hidden="true">✓</span>
                    </button>
                </template>
                <p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">{{ __('No options found.') }}</p>
            </div>
        </div>
    </div>
</x-form.field>
