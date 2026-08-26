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
    <div x-cloak x-show="showSubmitModal" @keydown.escape.window="cancelSubmission()" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="submit-assessment-title">
        <div class="absolute inset-0 bg-gray-950/50" @click="cancelSubmission()"></div>
        <div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900" @click.stop>
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 text-warning-700 dark:bg-warning-500/15 dark:text-warning-300"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                <div>
                    <h2 id="submit-assessment-title" class="text-lg font-semibold text-gray-900 dark:text-white">Submit assessment?</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300" x-text="submitMessage"></p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">After submission, your answers cannot be changed.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" @click="cancelSubmission()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Review answers</button>
                <button type="button" x-ref="confirmSubmit" @click="confirmSubmission()" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white"><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Confirm submission</span></button>
            </div>
        </div>
    </div>
    <div x-cloak x-show="courseCompleted" @keydown.escape.window="courseCompleted = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="course-completed-title">
        <div class="absolute inset-0 bg-gray-950/50"></div>
        <div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-7 text-center shadow-theme-xl dark:border-gray-800 dark:bg-gray-900" @click.stop>
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success-100 text-2xl text-success-600 dark:bg-success-500/15 dark:text-success-300"><i class="bi bi-trophy" aria-hidden="true"></i></div>
            <h2 id="course-completed-title" class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">Congratulations on completing the course!</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">You passed the assessment and completed every required course item.</p>
            <a :href="completionSummaryUrl" class="mt-6 inline-flex rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white">View the course summary</a>
        </div>
    </div>
</div>
@endsection
