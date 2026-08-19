@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-4xl">
    <x-common.page-breadcrumb :pageTitle="$attempt->courseAssessment->material->title" />
    <x-common.component-card :title="'Attempt '.$attempt->attempt_number" desc="Choose the answer or answers, then submit for automatic grading.">
        <form method="POST" action="{{ route('learning.course-assessment-attempts.submit', [$enrollment, $attempt]) }}" class="space-y-8">@csrf
            @foreach($attempt->courseAssessment->questions as $question)
                <fieldset class="rounded-xl border border-gray-200 p-5 dark:border-gray-800"><legend class="px-2 font-medium text-gray-800 dark:text-white">{{ $loop->iteration }}. {{ $question->prompt }} <span class="text-xs font-normal text-gray-500">({{ $question->type->label() }}, {{ $question->marks }} marks)</span></legend><div class="mt-3 space-y-2">@foreach($question->options as $option)<label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 hover:border-brand-300 dark:border-gray-800 dark:text-gray-300"><input type="{{ $question->type->value === 'multiple_choice' ? 'checkbox' : 'radio' }}" name="answers[{{ $question->id }}]{{ $question->type->value === 'multiple_choice' ? '[]' : '' }}" value="{{ $option->id }}" class="h-4 w-4 border-gray-300 text-brand-500">{{ $option->option_text }}</label>@endforeach</div></fieldset>
            @endforeach
            <div class="flex justify-end"><button class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white" onclick="return confirm('Submit this course assessment?')">Submit assessment</button></div>
        </form>
    </x-common.component-card>
</div>
@endsection
