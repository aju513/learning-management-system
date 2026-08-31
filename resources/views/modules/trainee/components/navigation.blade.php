@php
    $tabs = collect(config('trainee-navigation.tabs', []))
        ->filter(fn (array $tab): bool => ($tab['disabled'] ?? false) || auth()->user()?->can($tab['permission'] ?? ''))
        ->values();
@endphp

<nav class="border-t border-gray-100 dark:border-gray-800" aria-label="{{ __('Trainee navigation') }}">
    <div class="mx-auto max-w-(--breakpoint-2xl) overflow-x-auto px-4 sm:px-6">
        <div class="flex min-w-max items-center gap-2 py-2 sm:gap-4 sm:py-3">
            @foreach ($tabs as $tab)
                @php
                    $active = collect($tab['active'] ?? [])->contains(fn (string $pattern): bool => request()->routeIs($pattern));
                    $disabled = $tab['disabled'] ?? false;
                @endphp
                @if ($disabled)
                    <span aria-disabled="true" title="{{ __('Coming soon') }}" class="inline-flex min-h-12 cursor-not-allowed items-center whitespace-nowrap rounded-t-lg border-b-2 border-transparent px-4 text-sm font-medium text-gray-400 dark:text-gray-600 sm:px-5">{{ __($tab['label']) }}</span>
                @else
                    <a href="{{ route($tab['route']) }}" @if ($active) aria-current="page" @endif class="inline-flex min-h-12 items-center whitespace-nowrap rounded-t-lg border-b-2 px-4 text-sm font-semibold transition sm:px-5 {{ $active ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}">{{ __($tab['label']) }}</a>
                @endif
            @endforeach
        </div>
    </div>
</nav>
