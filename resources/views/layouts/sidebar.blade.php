@php
    $menuItems = app(\App\Services\NavigationService::class)->forUser(auth()->user());
@endphp

<aside id="sidebar"
    class="fixed left-0 top-0 z-99999 flex h-screen flex-col border-r border-gray-200 bg-white px-5 text-gray-900 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900"
    x-data="{ open: null }"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">
    <div class="flex pb-7 pt-8" :class="!$store.sidebar.isExpanded && !$store.sidebar.isHovered ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ route('portal.home') }}" class="flex items-center gap-3 font-semibold text-gray-900 dark:text-white">
            <img src="/images/logo/logo-icon.svg" alt="{{ config('app.name') }}" width="32" height="32">
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ config('app.name') }}</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto no-scrollbar">
        <p class="mb-4 text-xs uppercase text-gray-400" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ __('Menu') }}</p>
        <ul class="flex flex-col gap-1">
            @foreach ($menuItems as $item)
                @php
                    $hasChildren = isset($item['children']);
                    $childActive = $hasChildren && collect($item['children'])->contains(fn ($child) => request()->routeIs($child['route']));
                @endphp
                <li x-init="if ({{ $childActive ? 'true' : 'false' }}) open = '{{ $item['key'] }}'">
                    @if ($hasChildren)
                        <button type="button" @click="open = open === '{{ $item['key'] }}' ? null : '{{ $item['key'] }}'"
                            class="menu-item group w-full {{ $childActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                            :class="!$store.sidebar.isExpanded && !$store.sidebar.isHovered ? 'xl:justify-center' : 'xl:justify-start'">
                            <x-common.menu-icon :name="$item['icon'] ?? 'dashboard'" class="menu-item-icon" />
                            <span class="menu-item-text" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ __($item['label']) }}</span>
                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="ml-auto h-5 w-5" :class="open === '{{ $item['key'] }}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>
                        <ul x-show="open === '{{ $item['key'] }}' && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)" class="ml-9 mt-2 space-y-1">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ route($child['route']) }}" class="menu-dropdown-item group {{ request()->routeIs($child['route']) ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"><x-common.menu-icon :name="$child['icon'] ?? 'dashboard'" class="h-4 w-4" />{{ __($child['label']) }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ route($item['route']) }}" class="menu-item group {{ request()->routeIs($item['route']) ? 'menu-item-active' : 'menu-item-inactive' }}" :class="!$store.sidebar.isExpanded && !$store.sidebar.isHovered ? 'xl:justify-center' : 'xl:justify-start'">
                            <x-common.menu-icon :name="$item['icon'] ?? 'dashboard'" class="menu-item-icon" />
                            <span class="menu-item-text" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ __($item['label']) }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
