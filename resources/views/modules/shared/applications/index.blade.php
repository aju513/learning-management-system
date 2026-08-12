@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Course Applications" />
<x-common.component-card title="Review applications" desc="Approve applications to unlock course learning, or reject them with an optional explanation.">
    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[1fr_240px_180px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search trainee" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <x-form.select name="course_id" :options="$courses->pluck('title', 'id')" :value="request('course_id')" placeholder="All courses" />
        <x-form.select name="status" :options="['pending' => 'Pending', 'rejected' => 'Rejected']" :value="request('status')" placeholder="All statuses" />
        <button class="h-11 rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:text-white">Filter</button>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-4 py-3">Trainee</th><th class="px-4 py-3">Course</th><th class="px-4 py-3">Requested</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Decision</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($applications as $application)
                    <tr>
                        <td class="px-4 py-4"><p class="font-medium text-gray-800 dark:text-white">{{ $application->trainee->name }}</p><p class="text-xs text-gray-500">{{ $application->trainee->email }}</p></td>
                        <td class="px-4 py-4"><p class="text-sm text-gray-700 dark:text-gray-300">{{ $application->course->title }}</p><p class="text-xs text-gray-500">{{ $application->course->instructor?->name }}</p></td>
                        <td class="px-4 py-4 text-sm text-gray-500">{{ $application->requested_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-4"><x-ui.badge :color="$application->status->value === 'pending' ? 'warning' : 'error'">{{ ucfirst($application->status->value) }}</x-ui.badge>@if($application->review_note)<p class="mt-1 max-w-xs text-xs text-gray-500">{{ $application->review_note }}</p>@endif</td>
                        <td class="px-4 py-4">
                            @if ($application->status->value === 'pending')
                                <div class="flex min-w-72 flex-col gap-2">
                                    <form method="POST" action="{{ route($routePrefix.'.applications.approve', $application) }}">@csrf @method('PATCH')<button class="rounded-lg bg-success-500 px-3 py-2 text-xs font-medium text-white">Approve</button></form>
                                    <form method="POST" action="{{ route($routePrefix.'.applications.reject', $application) }}" class="flex gap-2">@csrf @method('PATCH')<input name="review_note" maxlength="1000" placeholder="Optional rejection reason" class="h-9 min-w-48 rounded-lg border border-gray-300 bg-transparent px-3 text-xs dark:border-gray-700 dark:text-white"><button class="rounded-lg bg-error-50 px-3 py-2 text-xs font-medium text-error-600">Reject</button></form>
                                </div>
                            @else
                                <span class="text-xs text-gray-500">Reviewed {{ $application->reviewed_at?->diffForHumans() }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No course applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $applications->links() }}</div>
</x-common.component-card>
@endsection
