<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open" class="flex items-center gap-3 text-left">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span class="hidden lg:block">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>
            <span class="block text-xs text-gray-500">{{ auth()->user()->roles->pluck('name')->join(', ') }}</span>
        </span>
        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
    </button>
    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-3 w-64 rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-100 px-3 pb-3 dark:border-gray-800">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
        </div>
        <a href="{{ route('admin.profile.edit') }}" class="mt-2 flex rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">My profile</a>
        <a href="{{ route('admin.password.edit') }}" class="flex rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">Change password</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-gray-100 pt-2 dark:border-gray-800">
            @csrf
            <button type="submit" class="flex w-full rounded-lg px-3 py-2 text-sm text-error-600 hover:bg-error-50 dark:hover:bg-error-500/10">Sign out</button>
        </form>
    </div>
</div>
