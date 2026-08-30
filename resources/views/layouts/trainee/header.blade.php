<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
    <div class="mx-auto flex min-h-16 max-w-(--breakpoint-2xl) items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('learning.dashboard') }}" class="flex shrink-0 items-center gap-3 font-semibold text-gray-900 dark:text-white" aria-label="{{ __('Trainee home') }}">
            <img class="h-8 w-auto" src="/images/logo/logo-icon.svg" alt="LMS" width="32" height="32">
            <span class="hidden sm:inline">LMS</span>
        </a>

        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
            @if (auth()->user()?->can('credit-scores.view-own'))
                <div class="hidden md:block"><x-header.credit-summary /></div>
            @endif
            <div class="hidden sm:block"><x-common.locale-switcher /></div>
            <x-common.theme-toggle />
            <x-header.user-dropdown />
        </div>
    </div>

    <x-trainee::navigation />
</header>
