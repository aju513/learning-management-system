@extends(request()->routeIs('learning.*') ? 'layouts.assessment' : 'layouts.app')

@section('content')
@php($expiresAt = $attempt->expires_at ?: $attempt->started_at?->copy()->addMinutes((int) $attempt->assessment->duration_minutes))
@php($remainingSeconds = $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : 0)
<div x-data="{
    remaining: {{ $remainingSeconds }}, submitting: false, submitError: '', saveState: 'Saved answers loaded', timer: null, saveTimer: null,
    showSubmitModal: false, submitMessage: '', draftKey: 'assessment-attempt-{{ $attempt->id }}',
    serverAnswers: @js($attempt->answers->mapWithKeys(fn ($answer) => [$answer->assessment_question_id => $answer->text_answer ?? $answer->selected_option_ids])->all()),
    init() {
        this.restoreDraft();
        this.timer = setInterval(() => {
            if (this.remaining > 0) this.remaining--;
            else { clearInterval(this.timer); this.submitAssessment(true); }
        }, 1000);
        if (this.remaining === 0) this.submitAssessment(true);
    },
    form() { return this.$root.querySelector('form'); },
    collectAnswers() {
        const answers = {};
        this.form().querySelectorAll('[data-answer-question]').forEach((input) => {
            const id = input.dataset.answerQuestion;
            if (input.type === 'checkbox') { if (input.checked) (answers[id] ??= []).push(input.value); }
            else if (input.type === 'radio') { if (input.checked) answers[id] = input.value; }
            else if (input.value.trim() !== '') answers[id] = input.value;
        });
        return answers;
    },
    restoreDraft() {
        let localAnswers = {};
        try { localAnswers = JSON.parse(localStorage.getItem(this.draftKey) || '{}'); } catch (error) { this.saveState = 'Unable to recover local answers'; }
        const answers = {...this.serverAnswers, ...localAnswers};
        Object.entries(answers).forEach(([id, value]) => this.form().querySelectorAll(`[data-answer-question='${id}']`).forEach((input) => {
            if (input.type === 'checkbox' || input.type === 'radio') input.checked = Array.isArray(value) ? value.map(String).includes(input.value) : String(value) === input.value;
            else input.value = value ?? '';
        }));
        if (Object.keys(localAnswers).length) this.saveState = 'Answers recovered locally';
    },
    saveDraft() {
        if (this.submitting || this.remaining <= 0) return;
        const answers = this.collectAnswers();
        localStorage.setItem(this.draftKey, JSON.stringify(answers));
        this.saveState = 'Saving…'; clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(async () => {
            try {
                const response = await fetch('{{ route('learning.assessments.attempts.answers.save', $attempt) }}', {method:'PATCH', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':this.form().querySelector('[name="_token"]').value}, body:JSON.stringify({answers})});
                if (!response.ok) throw new Error('Autosave failed');
                this.saveState = 'Saved';
            } catch (error) { this.saveState = 'Saved locally; server sync will retry'; }
        }, 500);
    },
    unansweredCount() { return this.form().querySelectorAll('fieldset').length - Object.keys(this.collectAnswers()).length; },
    submitAssessment(automatic = false) {
        if (this.submitting) return;
        if (automatic) { this.confirmSubmission(); return; }
        const unanswered = this.unansweredCount();
        this.submitMessage = unanswered ? `You have ${unanswered} unanswered question${unanswered === 1 ? '' : 's'}. Submit anyway?` : 'You have answered every question. Submit this attempt?';
        this.showSubmitModal = true;
        this.$nextTick(() => this.$refs.confirmSubmit?.focus());
    },
    cancelSubmission() { this.showSubmitModal = false; },
    async confirmSubmission() {
        if (this.submitting) return;
        this.showSubmitModal = false; this.submitting = true; this.submitError = ''; clearInterval(this.timer);
        try {
            const response = await fetch(this.form().action, {method:'POST', body:new FormData(this.form()), credentials:'same-origin', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Submission failed');
            localStorage.removeItem(this.draftKey);
            window.location.assign(payload.redirect);
        } catch (error) {
            this.submitting = false; this.submitError = 'Submission could not be confirmed. Your answers remain saved; check your connection and try again.'; this.saveState = 'Recovery available';
            if (this.remaining > 0) this.timer = setInterval(() => {
                if (this.remaining > 0) this.remaining--;
                else { clearInterval(this.timer); this.confirmSubmission(); }
            }, 1000);
        }
    }
}" x-init="init()" class="mx-auto max-w-4xl">
    <x-common.page-breadcrumb :pageTitle="$attempt->assessment->title" :translate="false">
        <x-slot:actions><div class="rounded-lg bg-warning-50 px-4 py-2 text-sm font-semibold text-warning-700 dark:bg-warning-500/10"><span x-text="`${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}`"></span> remaining</div></x-slot:actions>
    </x-common.page-breadcrumb>
    <x-common.component-card :title="'Attempt '.$attempt->attempt_number" :desc="$attempt->assessment->instructions ?? 'Answer every question, then submit for grading.'">
        <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.04]"><div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-gray-800 dark:text-white">Question progress</span><span class="text-gray-500">{{ $attempt->assessment->questions->count() }} questions</span></div><div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-500" :style="`width: ${(Object.keys(collectAnswers()).length / {{ max(1, $attempt->assessment->questions->count()) }}) * 100}%`"></div></div><p class="mt-2 text-xs text-gray-500">Answer saving status: <span class="font-medium" x-text="saveState"></span></p></div>
        <form method="POST" action="{{ route('learning.assessments.attempts.submit', $attempt) }}" class="space-y-8" @submit.prevent="submitAssessment()" @change="saveDraft" @input.debounce.700ms="saveDraft">
            @csrf
            @foreach($attempt->assessment->questions as $question)
                <fieldset class="rounded-xl border border-gray-200 p-5 dark:border-gray-800"><legend class="px-2 font-medium text-gray-800 dark:text-white">Question {{ $loop->iteration }} of {{ $attempt->assessment->questions->count() }}. {{ $question->prompt }} <span class="text-xs font-normal text-gray-500">({{ $question->marks }} marks)</span></legend>
                    @if($question->type === \App\Enums\QuestionType::QuestionAnswer)
                        <textarea name="answers[{{ $question->id }}]" data-answer-question="{{ $question->id }}" rows="4" class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="Write your answer"></textarea>
                    @else
                        <p class="mt-2 text-xs text-gray-500">{{ $question->type === \App\Enums\QuestionType::MultipleChoice ? 'Select all that apply' : 'Select one answer' }}</p><div class="mt-3 space-y-2">@foreach($question->options as $option)<label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-gray-800 dark:text-gray-300 dark:has-[:checked]:bg-brand-500/10"><input type="{{ $question->type === \App\Enums\QuestionType::MultipleChoice ? 'checkbox' : 'radio' }}" data-answer-question="{{ $question->id }}" name="answers[{{ $question->id }}]{{ $question->type === \App\Enums\QuestionType::MultipleChoice ? '[]' : '' }}" value="{{ $option->id }}" class="h-4 w-4 border-gray-300 text-brand-500">{{ $option->option_text }}</label>@endforeach</div>
                    @endif
                </fieldset>
            @endforeach
            <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs text-gray-500" x-text="saveState"></p><p x-show="submitError" x-text="submitError" class="mt-1 text-sm text-error-600" role="alert"></p></div><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60" :disabled="submitting"><span x-text="submitting ? 'Submitting…' : 'Submit assessment'"></span></button></div>
        </form>
    </x-common.component-card>
    <div x-cloak x-show="showSubmitModal" @keydown.escape.window="cancelSubmission()" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="submit-test-title"><div class="absolute inset-0 bg-gray-950/50" @click="cancelSubmission()"></div><div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900" @click.stop><h2 id="submit-test-title" class="text-lg font-semibold text-gray-900 dark:text-white">Submit test?</h2><p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300" x-text="submitMessage"></p><p class="mt-2 text-xs text-gray-500">After submission, your answers cannot be changed.</p><div class="mt-6 flex justify-end gap-3"><button type="button" @click="cancelSubmission()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium dark:border-gray-700">Review answers</button><button type="button" x-ref="confirmSubmit" @click="confirmSubmission()" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Confirm submission</button></div></div></div>
</div>
@endsection
