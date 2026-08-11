@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'mode' => 'single',
    'dateFormat' => 'Y-m-d',
    'placeholder' => 'Select date',
    'help' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $id ??= str_replace(['[]', '[', ']', '.'], ['', '-', '', '-'], $name);
    $currentValue = old($name, $value);
    $flatpickrValue = is_array($currentValue) ? $currentValue : ($currentValue ?: null);
    $inputClasses = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800';
@endphp

<x-form.field :name="$name" :label="$label" :id="$id" :required="$required" :error="$error" :help="$help">
    <div
        x-data="{
            flatpickrInstance: null,
            init() {
                this.flatpickrInstance = flatpickr(this.$refs.dateInput, {
                    mode: @js($mode),
                    static: true,
                    monthSelectorType: 'static',
                    dateFormat: @js($dateFormat),
                    defaultDate: @js($flatpickrValue),
                    onChange: (selectedDates, dateStr, instance) => {
                        this.$dispatch('date-change', { selectedDates, dateStr, instance });
                    }
                });
            },
            destroy() {
                this.flatpickrInstance?.destroy();
            }
        }"
        x-init="init()"
        x-on:destroy="destroy()"
        class="relative custom-datepicker"
    >
        <input
            x-ref="dateInput"
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ is_array($currentValue) ? '' : $currentValue }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @required($required)
            @disabled($disabled)
            {{ $attributes->merge(['class' => $inputClasses]) }}
        >
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M7.25 3.75v2m9.5-2v2M5.5 4.75h13A1.75 1.75 0 0 1 20.25 6.5v12A1.75 1.75 0 0 1 18.5 20.25h-13A1.75 1.75 0 0 1 3.75 18.5v-12A1.75 1.75 0 0 1 5.5 4.75Zm-1.75 4h16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
        </span>
    </div>
</x-form.field>
