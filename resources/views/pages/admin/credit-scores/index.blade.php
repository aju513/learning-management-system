@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$title" />
@if(! $fiscalYear)
    <x-common.component-card title="No active fiscal year"><p class="text-sm text-gray-500">Credit claims will appear after Super Admin activates a fiscal year.</p></x-common.component-card>
@else
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">{{ $fiscalYear->name }}</p><p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($claimedTotal, 2) }}</p><p class="text-xs text-gray-500">Claimed credits</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Ready to claim</p><p class="mt-2 text-2xl font-bold text-brand-500">{{ number_format($eligibleTotal, 2) }}</p><p class="text-xs text-gray-500">Eligible credits</p></div>
        @php($attendanceReady = ($attendance?->present_days ?? 0) >= $fiscalYear->attendance_threshold_days)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Attendance</p><p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $attendance?->present_days ?? 0 }} / {{ $fiscalYear->attendance_threshold_days }}</p><form class="mt-2" method="POST" action="{{ route('learning.credit-scores.attendance.refresh') }}">@csrf<button class="text-xs font-medium text-brand-500 hover:text-brand-700">Refresh attendance</button></form>@if(! $attendanceReady)<button disabled class="mt-3 w-full cursor-not-allowed rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-400 dark:bg-gray-800">Claim attendance credit</button>@else<p class="mt-3 text-xs font-medium text-success-600">Attendance threshold reached. Claim below.</p>@endif</div>
    </div>
    @if($attendance?->status === 'failed')<p class="mt-4 rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700">Attendance could not be captured: {{ $attendance->error_message }}</p>@endif
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-common.component-card title="Available to claim" desc="Claim each eligible activity once.">
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($eligibleAwards as $award)
                    <div class="flex items-center justify-between gap-4 py-4"><div><p class="font-medium text-gray-800 dark:text-white">{{ $award->source_label }}</p><p class="text-xs text-gray-500">{{ $award->source_type->label() }} · {{ $award->eligible_at?->format('M d, Y') }}</p></div><div class="flex items-center gap-3"><span class="font-semibold text-brand-500">{{ number_format($award->points, 2) }}</span><form method="POST" action="{{ route('learning.credit-scores.claim', $award) }}">@csrf<button class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white">Claim</button></form></div></div>
                @empty <p class="py-6 text-sm text-gray-500">No credits are ready to claim.</p> @endforelse
            </div>
            {{ $eligibleAwards->links() }}
        </x-common.component-card>
        <x-common.component-card title="Credit history" desc="Claimed and eligible credit activity for this fiscal year.">
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($history as $award)
                    <div class="flex items-center justify-between gap-4 py-4"><div><p class="font-medium text-gray-800 dark:text-white">{{ $award->source_label }}</p><p class="text-xs text-gray-500">{{ $award->source_type->label() }} · {{ $award->claimed_at?->format('M d, Y') ?? 'Not claimed' }}</p></div><x-ui.badge :color="$award->isClaimed() ? 'success' : 'warning'">{{ number_format($award->points, 2) }}</x-ui.badge></div>
                @empty <p class="py-6 text-sm text-gray-500">No credit history yet.</p> @endforelse
            </div>
            {{ $history->links() }}
        </x-common.component-card>
    </div>
@endif
@endsection
