export default function courseAssessmentPlayer({ draftKey, saveUrl, serverAnswers = {} }) {
    return {
        submitting: false,
        showSubmitModal: false,
        submitMessage: '',
        submitError: '',
        saveState: 'Not saved yet',
        courseCompleted: false,
        completionSummaryUrl: '',
        saveTimer: null,
        draftKey,
        saveUrl,
        serverAnswers,

        init() {
            this.applyAnswers(this.serverAnswers);
            this.restoreDraft();
        },

        form() {
            return this.$root.querySelector('form');
        },

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
            Object.entries(answers || {}).forEach(([id, value]) => {
                this.form().querySelectorAll(`[data-answer-question='${id}']`).forEach((input) => {
                    input.checked = Array.isArray(value)
                        ? value.map(String).includes(String(input.value))
                        : String(value) === String(input.value);
                });
            });
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
                    const response = await fetch(this.saveUrl, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.form().querySelector('[name="_token"]').value,
                        },
                        body: JSON.stringify({ answers }),
                    });

                    if (!response.ok) throw new Error('Autosave failed');
                    this.saveState = 'Answer saved';
                } catch (error) {
                    this.saveState = 'Saved locally; server sync will retry';
                }
            }, 500);
        },

        unansweredCount() {
            return this.form().querySelectorAll('fieldset').length - Object.keys(this.collectAnswers()).length;
        },

        submitAssessment() {
            if (this.submitting) return;

            const unanswered = this.unansweredCount();
            this.submitMessage = unanswered
                ? `You have ${unanswered} unanswered question${unanswered === 1 ? '' : 's'}. Submit anyway? Your answers cannot be changed afterward.`
                : 'You have answered all questions. Submit this course assessment? Your answers cannot be changed afterward.';
            this.showSubmitModal = true;
            this.$nextTick(() => this.$refs.confirmSubmit?.focus());
        },

        cancelSubmission() {
            this.showSubmitModal = false;
        },

        async confirmSubmission() {
            if (this.submitting) return;

            this.showSubmitModal = false;

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

                if (data.course_completed) {
                    this.courseCompleted = true;
                    this.completionSummaryUrl = data.summary_url || data.redirect;
                    this.saveState = 'Course completed';
                    return;
                }

                window.location.assign(data.redirect);
            } catch (error) {
                this.submitting = false;
                this.submitError = 'We could not submit your assessment.';
                this.saveState = 'Your answers have been preserved.';
            }
        },
    };
}
