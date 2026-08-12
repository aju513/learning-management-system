@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Assessment Submitted" />
<div class="mx-auto max-w-xl"><x-common.component-card :title="$attempt->assessment->title"><div class="py-8 text-center"><div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-success-50 text-2xl text-success-600 dark:bg-success-500/10">✓</div><h2 class="mt-4 text-xl font-semibold text-gray-800 dark:text-white">Your answers were submitted</h2><p class="mt-2 text-sm text-gray-500">Attempt {{ $attempt->attempt_number }} has been graded. Results are not configured for immediate trainee display.</p><a href="{{ route('learning.assessments.index') }}" class="mt-6 inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Back to my tests</a></div></x-common.component-card></div>
@endsection
