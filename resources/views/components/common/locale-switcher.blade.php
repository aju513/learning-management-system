<div class="flex items-center gap-1 rounded-full border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900" aria-label="{{ __('Language') }}">
    @foreach(config('app.supported_locales', []) as $locale => $localeLabel)
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="locale" value="{{ $locale }}">
            <button type="submit" class="rounded-full px-3 py-1.5 text-xs font-medium transition-colors {{ app()->getLocale() === $locale ? 'bg-brand-500 text-white' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
                {{ $localeLabel }}
            </button>
        </form>
    @endforeach
</div>
