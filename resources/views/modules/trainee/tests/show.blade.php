@extends('layouts.trainee.app')

@section('content')
@php($application = $assessment->applications->first())
@php($assigned = $assessment->assignments->isNotEmpty() || $assessment->attempts->isNotEmpty())
<x-common.page-breadcrumb :pageTitle="$assessment->title" :translate="false" />
<div class="grid gap-4 xl:grid-cols-[1fr_360px]">
    <div class="space-y-4">
        <x-common.component-card :title="$assessment->title" :desc="$assessment->category?->name ?? 'Published test'">
            <div class="prose max-w-none text-sm leading-7 text-gray-600 dark:text-gray-300">{!! nl2br(e($assessment->description ?: 'No additional test description has been added yet.')) !!}</div>
        </x-common.component-card>
        <x-common.component-card title="Before you start">
            @if($assigned)
                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">This test is already connected to your account. Open My Tests to see its current state and attempt history.</p>
                <a href="{{ route('learning.assessments.show', $assessment) }}" class="mt-5 inline-flex rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white">Open My Test</a>
            @elseif($application?->status === \App\Enums\AssessmentApplicationStatus::Pending)
                <x-ui.alert variant="warning" title="Application pending" message="Your application is waiting for review. Applying does not grant test access until it is approved." />
            @else
                @if($application?->status === \App\Enums\AssessmentApplicationStatus::Rejected)
                    <x-ui.alert variant="error" title="Application rejected" :message="$application->review_note ?: 'You may submit another application when you are ready.'" />
                @elseif($application?->status === \App\Enums\AssessmentApplicationStatus::Cancelled)
                    <x-ui.alert variant="warning" title="Access removed" message="Your previous access was removed. You may apply again." />
                @endif
                <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">Submit an application for review. Approval creates your test assignment and unlocks the taking flow.</p>
                <form method="POST" action="{{ route('learning.test-applications.store', $assessment) }}" class="mt-5">
                    @csrf
                    <button class="inline-flex rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white">{{ $application ? 'Apply again' : 'Apply for this test' }}</button>
                </form>
            @endif
        </x-common.component-card>
    </div>
    <aside>
        <x-common.component-card title="Test information">
            <dl class="space-y-4 text-sm">
                <div><dt class="text-gray-500">Category</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->category?->name ?? 'Uncategorized' }}</dd></div>
                <div><dt class="text-gray-500">Questions</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->questions_count }}</dd></div>
                <div><dt class="text-gray-500">Duration</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->duration_minutes }} minutes</dd></div>
                <div><dt class="text-gray-500">Passing score</dt><dd class="font-medium text-gray-800 dark:text-white">{{ rtrim(rtrim(number_format((float) $assessment->passing_percentage, 2), '0'), '.') }}%</dd></div>
                <div><dt class="text-gray-500">Attempts</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->max_attempts }}</dd></div>
            </dl>
            <a href="{{ route('learning.assessments.catalog') }}" class="mt-6 block rounded-lg border border-brand-500 px-4 py-3 text-center text-sm font-semibold text-brand-600 dark:text-brand-300">Back to test catalog</a>
        </x-common.component-card>
    </aside>
</div>
@endsection
