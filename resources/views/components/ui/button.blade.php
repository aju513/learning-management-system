@props([
    'size' => 'md',          
    'variant' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
    'className' => '',
    'disabled' => false,
])

@php
    // Base classes
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900';

    // Size map
    $sizeMap = [
        'sm' => 'px-4 py-3 text-sm',
        'md' => 'px-5 py-3.5 text-sm',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    // Variant map
    $variantMap = [
        'primary' => 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 focus:ring-brand-500 disabled:bg-brand-300',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 disabled:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:disabled:bg-gray-800',
        'success' => 'bg-success-500 text-white hover:bg-success-600 focus:ring-success-500 disabled:bg-success-300',
        'danger' => 'bg-error-500 text-white hover:bg-error-600 focus:ring-error-500 disabled:bg-error-300',
        'warning' => 'bg-warning-500 text-white hover:bg-warning-600 focus:ring-warning-500 disabled:bg-warning-300',
        'info' => 'bg-blue-light-500 text-white hover:bg-blue-light-600 focus:ring-blue-light-500 disabled:bg-blue-light-300',
        'outline' => 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:ring-gray-400 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300',
    ];
    $variantClass = $variantMap[$variant] ?? $variantMap['primary'];

    // disabled classes
    $disabledClass = $disabled ? 'cursor-not-allowed opacity-50' : '';

    // final classes (merge user className too)
    $classes = trim("{$base} {$sizeClass} {$variantClass} {$className} {$disabledClass}");
@endphp

<button
    {{ $attributes->merge(['class' => $classes, 'type' => $attributes->get('type', 'button')]) }}
    @if($disabled) disabled @endif
>
    {{-- start icon: priority — named slot 'startIcon' first, then startIcon prop if it's a HtmlString --}}
    @if (isset($__env) && $slot->isEmpty() === false) @endif

    @hasSection('startIcon')
        <span class="flex items-center">
            @yield('startIcon')
        </span>
    @elseif($startIcon)
        <span class="flex items-center">{!! $startIcon !!}</span>
    @endif

    {{-- main slot --}}
    {{ $slot }}

    {{-- end icon: named slot 'endIcon' first, then endIcon prop --}}
    @hasSection('endIcon')
        <span class="flex items-center">
            @yield('endIcon')
        </span>
    @elseif($endIcon)
        <span class="flex items-center">{!! $endIcon !!}</span>
    @endif
</button>
