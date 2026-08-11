@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Activity Log" />
<x-common.component-card title="Audit trail" desc="Administrative and account security events. Sensitive values are never recorded.">
    <form class="grid gap-3 md:grid-cols-5">
        <input name="event" value="{{ request('event') }}" placeholder="Event" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
        <input name="actor" value="{{ request('actor') }}" placeholder="Actor" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
        <input name="from" type="date" value="{{ request('from') }}" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
        <input name="to" type="date" value="{{ request('to') }}" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
        <button class="rounded-lg border border-gray-300 text-sm font-medium dark:border-gray-700 dark:text-white">Filter</button>
    </form>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-4 py-3">Time</th><th class="px-4 py-3">Event</th><th class="px-4 py-3">Actor</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Context</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@forelse($activities as $activity)<tr class="align-top text-sm"><td class="whitespace-nowrap px-4 py-4 text-gray-500">{{ $activity->created_at->format('Y-m-d H:i') }}</td><td class="px-4 py-4"><x-ui.badge color="info">{{ $activity->event ?: $activity->log_name }}</x-ui.badge></td><td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $activity->causer?->name ?? 'System' }}</td><td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $activity->description }}</td><td class="max-w-sm px-4 py-4"><code class="text-xs text-gray-500">{{ $activity->properties->isEmpty() ? '—' : $activity->properties->toJson() }}</code></td></tr>@empty<tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No activity found.</td></tr>@endforelse</tbody></table></div>
    {{ $activities->links() }}
</x-common.component-card>
@endsection
