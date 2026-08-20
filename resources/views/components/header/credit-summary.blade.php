<a href="{{ route('learning.credit-scores.index') }}" class="relative flex items-center gap-3 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-left transition hover:border-brand-400 dark:border-brand-500/30 dark:bg-brand-500/10" title="View fiscal-year credit history">
    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-500 text-white"><i class="bi bi-award" aria-hidden="true"></i></span>
    <span class="hidden sm:block"><span class="block text-xs text-brand-600 dark:text-brand-300">{{ $summary['fiscalYear']?->name ?? 'Fiscal Year' }}</span><span class="block text-sm font-semibold text-gray-800 dark:text-white">{{ number_format($summary['claimedTotal'], 2) }} credits</span></span>
    @if($summary['eligibleCount'] > 0)<span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-bold text-white">{{ $summary['eligibleCount'] }}</span>@endif
</a>
