@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Test Applications" />
<x-common.component-card title="Review test applications" desc="Approve an eligible applicant to create their test assignment, or reject the request with an optional explanation.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_240px_180px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search trainee" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <x-form.select name="assessment_id" :options="$assessments->pluck('title', 'id')" :value="request('assessment_id')" placeholder="All tests" />
        <x-form.select name="status" :options="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled']" :value="request('status')" placeholder="All statuses" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
    </form>
    @php($groupedApplications = $applications->getCollection()->groupBy('assessment_id'))
    <div class="space-y-3">
        @forelse($groupedApplications as $assessmentId => $testApplications)
            @php($test = $assessments->firstWhere('id', $assessmentId) ?? $testApplications->first()->assessment)
            <section x-data="{ expanded: true }" class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-brand-50/60 dark:hover:bg-brand-500/5"><span><strong class="block text-gray-800 dark:text-white">{{ $test->title }}</strong><span class="mt-1 block text-xs text-gray-500">{{ $testApplications->count() }} applications in this view</span></span><span class="flex items-center gap-2"><span class="rounded-full bg-warning-50 px-2.5 py-1 text-xs text-warning-700">Applied {{ $test->applied_count ?? $testApplications->count() }}</span><span class="rounded-full bg-success-50 px-2.5 py-1 text-xs text-success-700">Approved {{ $test->approved_count ?? 0 }}</span><i class="bi" :class="expanded ? 'bi-dash' : 'bi-plus'"></i></span></button>
                <div x-show="expanded" x-collapse class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-800 dark:border-gray-800">
                    @foreach($testApplications as $application)
                        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-medium text-gray-800 dark:text-white">{{ $application->trainee->name }}</p><p class="text-xs text-gray-500">{{ $application->trainee->email }} · Applied {{ $application->requested_at->format('M j, Y H:i') }}</p>@if($application->review_note)<p class="mt-1 text-xs text-gray-500">{{ $application->review_note }}</p>@endif</div><div class="flex flex-wrap items-center gap-2"><x-ui.badge :color="$application->status->value === 'pending' ? 'warning' : ($application->status->value === 'approved' ? 'success' : 'error')">{{ ucfirst($application->status->value) }}</x-ui.badge>@if($application->status->value === 'pending')<form method="POST" action="{{ route($routePrefix.'.test-applications.approve', $application) }}">@csrf @method('PATCH')<button class="rounded-lg bg-success-500 px-3 py-2 text-xs font-medium text-white">Approve</button></form><form method="POST" action="{{ route($routePrefix.'.test-applications.reject', $application) }}" class="flex gap-2">@csrf @method('PATCH')<input name="review_note" maxlength="1000" placeholder="Optional reason" class="h-9 min-w-44 rounded-lg border border-gray-300 bg-transparent px-3 text-xs dark:border-gray-700 dark:text-white"><button class="rounded-lg bg-error-50 px-3 py-2 text-xs font-medium text-error-600">Reject</button></form>@endif</div></div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">No test applications found.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $applications->links() }}</div>
</x-common.component-card>
@endsection
