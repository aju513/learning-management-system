@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="My Applications" />
<x-common.component-card title="Course applications" desc="Track requests waiting for an Admin or the course Instructor.">
    <div class="space-y-4">
        @forelse ($applications as $application)
            <div class="flex flex-col justify-between gap-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800 sm:flex-row sm:items-center">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-white">{{ $application->course->title }}</h2>
                    <p class="text-sm text-gray-500">{{ $application->course->instructor?->name }} · Requested {{ $application->requested_at?->diffForHumans() ?? 'through direct assignment' }}</p>
                    @if ($application->review_note)<p class="mt-2 text-sm text-error-600">Review note: {{ $application->review_note }}</p>@endif
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.badge :color="$application->status->value === 'pending' ? 'warning' : ($application->status->value === 'rejected' ? 'error' : 'light')">{{ ucfirst($application->status->value) }}</x-ui.badge>
                    @if (in_array($application->status->value, ['rejected', 'cancelled'], true))
                        <form method="POST" action="{{ route('learning.applications.store', $application->course) }}">@csrf<button class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white">Apply again</button></form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700"><p class="text-sm text-gray-500">You have no course applications yet.</p><a href="{{ route('learning.catalog.index') }}" class="mt-3 inline-flex text-sm font-medium text-brand-500">Browse the catalog</a></div>
        @endforelse
    </div>
</x-common.component-card>
@endsection
