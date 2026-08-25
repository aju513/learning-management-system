@extends('layouts.learning')

@section('content')
<div x-data="courseAssessmentPlayer({ draftKey: @js('course-assessment-attempt-'.$attempt->id), saveUrl: @js(route('learning.course-assessment-attempts.answers.save', [$enrollment, $attempt])), serverAnswers: @js($attempt->answers->mapWithKeys(fn ($answer) => [$answer->course_assessment_question_id => $answer->selected_option_ids])->all()) })" x-init="init()" class="mx-auto max-w-4xl">
    <x-common.page-breadcrumb :pageTitle="$attempt->courseAssessment->material->title" />
    <x-common.component-card :title="'Attempt '.$attempt->attempt_number" desc="Choose the answer or answers, then submit for automatic grading.">
        <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.04]">
            <div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-gray-800 dark:text-white">Assessment unlocked</span><span class="text-gray-600 dark:text-gray-400">{{ $attempt->courseAssessment->questions->count() }} {{ Str::plural('question', $attempt->courseAssessment->questions->count()) }} · Passing score {{ $attempt->courseAssessment->passing_percentage }}%</span></div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" :style="`width: ${(Object.keys(collectAnswers()).length / {{ max(1, $attempt->courseAssessment->questions->count()) }}) * 100}%`"></div></div>
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Answer saving status: <span class="font-medium" x-text="saveState"></span></p>
        </div>
        <form method="POST" action="{{ route('learning.course-assessment-attempts.submit', [$enrollment, $attempt]) }}" class="space-y-8" @submit.prevent="submitAssessment()" @change="saveDraft">
            @csrf
            @foreach($attempt->courseAssessment->questions as $question)
                <fieldset class="rounded-xl border border-gray-200 p-5 dark:border-gray-800">
                    <legend class="px-2 font-medium text-gray-800 dark:text-white">Question {{ $loop->iteration }} of {{ $attempt->courseAssessment->questions->count() }}. {{ $question->prompt }} <span class="text-xs font-normal text-gray-600 dark:text-gray-400">({{ $question->type->label() }}, {{ $question->marks }} marks)</span></legend>
                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ $question->type->value === 'multiple_choice' ? 'Select all that apply' : 'Select one answer' }}</p>
                    <div class="mt-3 space-y-2">
                        @foreach($question->options as $option)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-gray-800 dark:text-gray-300 dark:has-[:checked]:bg-brand-500/10">
                                <input type="{{ $question->type->value === 'multiple_choice' ? 'checkbox' : 'radio' }}" data-answer-question="{{ $question->id }}" name="answers[{{ $question->id }}]{{ $question->type->value === 'multiple_choice' ? '[]' : '' }}" value="{{ $option->id }}" class="h-4 w-4 border-gray-300 text-brand-500">
                                {{ $option->option_text }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><p class="text-xs text-gray-600 dark:text-gray-400" x-text="saveState"></p><p x-show="submitError" x-text="submitError" class="mt-1 text-sm text-error-600" role="alert"></p><button x-show="submitError" type="button" @click="submitAssessment()" class="mt-2 text-sm font-medium text-brand-600">Try again</button></div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60" :disabled="submitting"><i class="bi bi-check2-circle" aria-hidden="true"></i><span x-text="submitting ? 'Submitting assessment…' : 'Submit assessment'">Submit assessment</span></button>
            </div>
        </form>
    </x-common.component-card>
</div>
@endsection
