@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Activity Log" />
<x-common.component-card title="Audit trail" desc="A readable history of administrative and account security events. Sensitive values are never recorded.">
    <form method="GET" action="{{ route('admin.activity.index') }}" class="grid gap-3 md:grid-cols-2 lg:grid-cols-[1fr_1fr_170px_170px_auto] lg:items-end">
        <x-form.input name="event" label="Event" :value="request('event')" placeholder="Search events" />
        <x-form.input name="actor" label="Performed by" :value="request('actor')" placeholder="Name or email" />
        <x-form.input name="from" label="From" type="date" :value="request('from')" />
        <x-form.input name="to" label="To" type="date" :value="request('to')" />
        <div class="flex items-center gap-2 lg:pb-0.5">
            <button type="submit" class="h-11 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600">Filter</button>
            @if(request()->hasAny(['event', 'actor', 'from', 'to']))
                <a href="{{ route('admin.activity.index') }}" class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400">Clear</a>
            @endif
        </div>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-4 dark:border-gray-800">
        <div>
            <p class="font-medium text-gray-800 dark:text-white">Recent activity</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($activities->total()) }} {{ $activities->total() === 1 ? 'record' : 'records' }} found</p>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Newest events appear first</p>
    </div>

    <div class="mt-4 space-y-3">
        @forelse($activities as $activity)
            @php
                $eventKey = $activity->event ?: $activity->log_name ?: 'activity';
                $eventLabel = str($eventKey)->replace(['.', '_', '-'], ' ')->title();
                $eventColor = match (true) {
                    str($eventKey)->contains(['failed', 'deleted']) => 'error',
                    str($eventKey)->contains(['created', 'login', 'reset']) => 'success',
                    str($eventKey)->contains(['updated', 'changed', 'logout']) => 'info',
                    default => 'light',
                };
                $actorName = $activity->causer?->name ?? 'System';
                $actorInitial = str($actorName)->substr(0, 1)->upper();
                $subjectType = $activity->subject_type ? class_basename($activity->subject_type) : null;
                $subjectName = $activity->subject?->name ?? $activity->subject?->email ?? ($subjectType && $activity->subject_id ? $subjectType.' #'.$activity->subject_id : null);
                $properties = collect($activity->properties ?? []);
                $attributeValues = is_array($properties->get('attributes')) ? collect($properties->get('attributes')) : collect();
                $oldValues = is_array($properties->get('old')) ? collect($properties->get('old')) : collect();
                $sensitiveKeys = ['password', 'token', 'secret', 'credential', 'session'];
                $changedKeys = $attributeValues->keys()->merge($oldValues->keys())->unique()->reject(fn ($key) => str((string) $key)->lower()->contains($sensitiveKeys));
                $extraProperties = $properties->except(['attributes', 'old'])->reject(fn ($value, $key) => str((string) $key)->lower()->contains($sensitiveKeys));
                $formatValue = static function ($value): string {
                    if ($value === null || $value === '') return 'None';
                    if ($value instanceof BackedEnum) return (string) $value->value;
                    if (is_bool($value)) return $value ? 'Yes' : 'No';
                    if (is_array($value)) return implode(', ', array_map(static fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
                    return (string) $value;
                };
            @endphp
            <article class="rounded-xl border border-gray-100 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.02] sm:p-5">
                <div class="grid gap-4 md:grid-cols-[145px_minmax(0,1.5fr)_minmax(150px,0.8fr)_minmax(170px,0.9fr)] md:items-start">
                    <div class="flex items-start gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4l2.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <span><span class="block font-medium text-gray-700 dark:text-gray-300">{{ $activity->created_at->format('M d, Y') }}</span><span class="block text-xs">{{ $activity->created_at->format('H:i') }}</span></span>
                    </div>

                    <div class="flex min-w-0 gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400"><x-common.menu-icon name="activity-log" class="h-4 w-4" /></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2"><x-ui.badge :color="$eventColor" size="sm">{{ $eventLabel }}</x-ui.badge><span class="text-xs text-gray-400">{{ $activity->log_name }}</span></div>
                            <p class="mt-1 font-medium text-gray-800 dark:text-white">{{ $activity->description }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Performed by</p>
                        <div class="mt-1.5 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $actorInitial }}</span><span class="min-w-0"><span class="block truncate text-sm font-medium text-gray-700 dark:text-gray-200">{{ $actorName }}</span>@if($activity->causer?->email)<span class="block truncate text-xs text-gray-500">{{ $activity->causer->email }}</span>@endif</span></div>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Affected item</p>
                        <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $subjectName ?? 'System event' }}</p>
                        @if($subjectType)<p class="mt-0.5 text-xs text-gray-500">{{ $subjectType }}</p>@endif
                    </div>
                </div>

                @if($changedKeys->isNotEmpty() || $extraProperties->isNotEmpty())
                    <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Details</p>
                        <div class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($changedKeys as $key)
                                <div class="min-w-0"><span class="text-gray-500 dark:text-gray-400">{{ str($key)->replace(['_', '-'], ' ')->title() }}:</span> <span class="break-words text-gray-700 dark:text-gray-200">@if($oldValues->has($key)){{ $formatValue($oldValues->get($key)) }} → @endif{{ $formatValue($attributeValues->get($key)) }}</span></div>
                            @endforeach
                            @foreach($extraProperties as $key => $value)
                                <div class="min-w-0"><span class="text-gray-500 dark:text-gray-400">{{ str($key)->replace(['_', '-'], ' ')->title() }}:</span> <span class="break-words text-gray-700 dark:text-gray-200">{{ $formatValue($value) }}</span></div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">No additional details recorded.</p>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800"><x-common.menu-icon name="activity-log" /></span>
                <p class="mt-3 font-medium text-gray-800 dark:text-white">No activity found</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting the filters or check back after more activity is recorded.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $activities->links() }}</div>
</x-common.component-card>
@endsection
