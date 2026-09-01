@extends('layouts.trainee.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$assessment->title" :translate="false" />
<div class="grid gap-4 xl:grid-cols-[1fr_360px]">
    <div class="space-y-4">
        <x-common.component-card :title="$assessment->title" :desc="$assessment->category?->name ?? 'Published test'">
            <div class="prose max-w-none text-sm leading-7 text-gray-600 dark:text-gray-300">{!! nl2br(e($assessment->description ?: 'No additional test description has been added yet.')) !!}</div>
        </x-common.component-card>
        <x-common.component-card title="Before you start">
            <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">This test is assigned by an administrator. Once assigned, it will be available in <a href="{{ route('learning.assessments.index') }}" class="font-semibold text-brand-500">My Tests</a>, where you can start it and view your attempts.</p>
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
