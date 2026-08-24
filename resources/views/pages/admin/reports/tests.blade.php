@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Test Reports" />
<x-common.component-card title="Test performance report" desc="Review attempts, pass rates, and average scores for every test.">
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-4 py-3">{{ __('Tests') }}</th><th class="px-4 py-3">{{ __('Attempts') }}</th><th class="px-4 py-3">{{ __('Pass / Fail') }}</th><th class="px-4 py-3">{{ __('Average') }}</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@forelse($assessments as $assessment)<tr><td class="px-4 py-4 font-medium text-gray-800 dark:text-white">{{ $assessment->title }}</td><td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $assessment->attempts_count }}</td><td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $assessment->pass_count }} / {{ $assessment->fail_count }}</td><td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $assessment->average_score ?? 0 }}%</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No tests.') }}</td></tr>@endforelse</tbody></table></div>
</x-common.component-card>
@endsection
