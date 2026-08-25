@extends('layouts.learning')

@section('content')
<div x-data="{
    submitting: false,
    submitError: '',
    saveState: 'Not saved yet',
    saveTimer: null,
    draftKey: 'course-assessment-attempt-{{ $attempt->id }}',
    serverAnswers: @js($attempt->answers->mapWithKeys(fn ($answer) => [$answer->course_assessment_question_id => $answer->selected_option_ids])->all()),
    init() { this.applyAnswers(this.serverAnswers); this.restoreDraft(); },
    form() { return this.$root.querySelector('form'); },
    collectAnswers() {
        const answers = {};
        this.form().querySelectorAll('[data-answer-question]').forEach((input) => {
            const id = input.dataset.answerQuestion;
            if (input.type === 'checkbox') {
                if (input.checked) (answers[id] ??= []).push(input.value);
            } else if (input.checked) {
                answers[id] = input.value;
            }
        });
        return answers;
    },
    applyAnswers(answers) {
        Object.entries(answers || {}).forEach(([id, value]) => this.form().querySelectorAll(`[data-answer-question='${id}']`).forEach((input) => {
            input.checked = Array.isArray(value) ? value.map(String).includes(String(input.value)) : String(value) === String(input.value);
        }));
    },
    restoreDraft() {
        try {
            const answers = JSON.parse(localStorage.getItem(this.draftKey) || '{}');
            this.applyAnswers(answers);
            if (Object.keys(answers).length) this.saveState = 'Answers recovered locally';
        } catch (error) {
            this.saveState = 'Unable to recover saved answers';
        }
    },
    saveDraft() {
        if (this.submitting) return;
        const answers = this.collectAnswers();
        localStorage.setItem(this.draftKey, JSON.stringify(answers));
        this.saveState = 'Saving answer…';
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(async () => {
            try {
                const response = await fetch('{{ route('learning.course-assessment-attempts.answers.save', [$enrollment, $attempt]) }}', {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.form().querySelector('[name="_token"]').value },
                    body: JSON.stringify({ answers }),
                });
                if (!response.ok) throw new Error('Autosave failed');
                this.saveState = 'Answer saved';
            } catch (error) {
                this.saveState = 'Saved locally; server sync will retry';
            }
        }, 500);
    },
    unansweredCount() { return this.form().querySelectorAll('fieldset').length - Object.keys(this.collectAnswers()).length; },
    async submitAssessment() {
        const unanswered = this.unansweredCount();
        const message = unanswered
            ? `You have ${unanswered} unanswered question${unanswered === 1 ? '' : 's'}. Submit anyway? Your answers cannot be changed afterward.`
            : 'You have answered all questions. Submit this course assessment? Your answers cannot be changed afterward.';
        if (this.submitting || !window.confirm(message)) return;
        this.submitting = true;
        this.submitError = '';
        this.saveState = 'Submitting assessment…';
        try {
            const response = await fetch(this.form().action, {
                method: 'POST',
                body: new FormData(this.form()),
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Submission failed');
            const data = await response.json();
            localStorage.removeItem(this.draftKey);
            this.saveState = 'Assessment submitted successfully';
            window.location.assign(data.redirect);
        } catch (error) {
            this.submitting = false;
            this.submitError = 'We could not submit your assessment.';
            this.saveState = 'Your answers have been preserved.';
        }
    },
}" x-init="init()" class="mx-auto max-w-4xl">
    <x-common.page-breadcrumb :pageTitle="$attempt->courseAssessment->material->title" />
    <x-common.component-card :title="'Attempt '.$attempt->attempt_number" desc="Choose the answer or answers, then submit for automatic grading.">
        <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.04]">
            <div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-gray-800 dark:text-white">Assessment unlocked</span><span class="text-gray-600 dark:text-gray-400">{{ $attempt->courseAssessment->questions->count() }} questions · Passing score {{ $attempt->courseAssessment->passing_percentage }}%</span></div>
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
