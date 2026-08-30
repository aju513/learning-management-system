@extends('layouts.trainee.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Applied Courses">
    <x-slot:actions>
        <a href="{{ route('learning.catalog.index') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Find a course</a>
    </x-slot:actions>
</x-common.page-breadcrumb>

<x-common.component-card title="Applied courses" desc="Requests are shown newest first. Rejected or cancelled requests can be submitted again.">
    @if($applications->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Review note</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($applications as $application)
                        <tr class="align-middle">
                            <td class="px-4 py-4">
                                <a href="{{ route('learning.catalog.show', $application->course) }}" class="group flex min-w-[240px] items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 font-bold text-brand-500 dark:bg-brand-500/10">{{ Str::upper(Str::substr($application->course->title, 0, 1)) }}</span>
                                    <span><span class="block font-semibold text-gray-800 group-hover:text-brand-500 dark:text-white">{{ $application->course->title }}</span><span class="mt-1 block text-xs text-gray-500">{{ $application->course->instructor?->name ?? 'Instructor pending' }}</span></span>
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">{{ $application->requested_at?->format('M d, Y') ?? 'Direct assignment' }}</td>
                            @php($approved = in_array($application->status->value, ['active', 'completed'], true))
                            <td class="min-w-[260px] px-4 py-4"><x-ui.badge :color="$application->status->value === 'pending' ? 'warning' : ($application->status->value === 'rejected' ? 'error' : ($approved ? 'success' : 'light'))">{{ $approved ? 'Enrolled' : ucfirst($application->status->value) }}</x-ui.badge><div class="mt-3 flex items-center gap-1 text-[10px] font-medium"><span class="rounded bg-brand-50 px-1.5 py-1 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">Applied</span><span class="text-gray-300">→</span><span class="rounded bg-warning-50 px-1.5 py-1 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">Review</span><span class="text-gray-300">→</span><span class="rounded px-1.5 py-1 {{ $approved ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' : 'bg-gray-100 text-gray-500 dark:bg-white/[0.06]' }}">Approved</span><span class="text-gray-300">→</span><span class="rounded px-1.5 py-1 {{ $approved ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' : 'bg-gray-100 text-gray-500 dark:bg-white/[0.06]' }}">Enrolled</span></div><p class="mt-2 text-xs text-gray-500">@if($application->status->value === 'pending')Your application is awaiting instructor approval.@elseif($approved)Your application was approved. You can now start this course.@elseif($application->status->value === 'rejected')Review the note and apply again when ready.@else This application is no longer active. @endif</p></td>
                            <td class="max-w-xs px-4 py-4 text-sm text-gray-500">{{ $application->review_note ?: 'No note added.' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right">
                                @if(in_array($application->status->value, ['rejected', 'cancelled'], true))
                                    <form method="POST" action="{{ route('learning.applications.store', $application->course) }}">@csrf<button class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white">Apply again</button></form>
                                @else
                                    <a href="{{ route('learning.catalog.show', $application->course) }}" class="text-sm font-medium text-brand-500">View course</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-2xl text-brand-500 dark:bg-brand-500/10">+</div>
            <h2 class="mt-4 font-semibold text-gray-800 dark:text-white">No applications yet</h2>
            <p class="mt-1 text-sm text-gray-500">Explore the catalog and apply for a course that matches your goals.</p>
            <a href="{{ route('learning.catalog.index') }}" class="mt-5 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Browse courses</a>
        </div>
    @endif
</x-common.component-card>
@endsection
