@extends('layouts.learning')

@section('content')
<x-common.page-breadcrumb pageTitle="Assessment result" />
@php($correctAnswers = $attempt->answers->where('is_correct', true)->count())
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-2xl border p-8 {{ $attempt->passed ? 'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' : 'border-error-200 bg-error-50 dark:border-error-500/30 dark:bg-error-500/10' }}">
        <p class="text-sm font-semibold uppercase tracking-wide {{ $attempt->passed ? 'text-success-600' : 'text-error-600' }}">Assessment {{ $attempt->passed ? 'passed' : 'not passed' }}</p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-5xl font-bold text-gray-900 dark:text-white">{{ number_format((float) $attempt->score_percentage, 0) }}%</h1><p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $attempt->earned_marks }} / {{ $attempt->total_marks }} marks</p></div><span class="rounded-full bg-white/70 px-4 py-2 text-sm font-semibold text-gray-700 dark:bg-gray-950/20 dark:text-gray-200">Passing score {{ $attempt->courseAssessment->passing_percentage }}%</span></div>
    </section>

    <x-common.component-card title="Result summary" desc="Your course assessment has been graded and this attempt is now read-only.">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
            <div><p class="text-xs text-gray-600 dark:text-gray-400">Correct answers</p><p class="mt-1 text-lg font-semibold text-success-700">{{ $correctAnswers }}</p></div>
            <div><p class="text-xs text-gray-600 dark:text-gray-400">Questions</p><p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $attempt->courseAssessment->questions->count() }}</p></div>
            <div><p class="text-xs text-gray-600 dark:text-gray-400">Passing score</p><p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $attempt->courseAssessment->passing_percentage }}%</p></div>
            <div><p class="text-xs text-gray-600 dark:text-gray-400">Attempt</p><p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $attempt->attempt_number }}</p></div>
            <div><p class="text-xs text-gray-600 dark:text-gray-400">Completed</p><p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $attempt->submitted_at?->format('M j, Y') ?? '—' }}</p></div>
        </div>
    </x-common.component-card>

    @if($attempt->passed && $courseCreditPoints > 0)
        <section class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">Course credit score</p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">+{{ number_format($courseCreditPoints, 2) }} credits</h2>
                    @if($creditAward?->isClaimed())
                        <p class="mt-2 text-sm font-medium text-success-700 dark:text-success-300">This credit score has already been claimed.</p>
                    @elseif($creditAward)
                        <p class="mt-2 text-sm text-brand-800 dark:text-brand-200">You passed the assessment and your course credit score is ready to claim.</p>
                    @elseif($enrollment->status->value === 'completed')
                        <p class="mt-2 text-sm text-brand-800 dark:text-brand-200">This course was completed earlier. Claim your course credit score now.</p>
                    @else
                        <p class="mt-2 text-sm text-brand-800 dark:text-brand-200">Complete the remaining course items to unlock this credit score.</p>
                    @endif
                </div>
                @if($creditAward && ! $creditAward->isClaimed())
                    <form method="POST" action="{{ route('learning.credit-scores.claim', $creditAward) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">Claim {{ number_format($courseCreditPoints, 2) }} credits</button>
                    </form>
                @elseif($enrollment->status->value === 'completed')
                    <form method="POST" action="{{ route('learning.credit-scores.course-claim', $enrollment) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">Claim {{ number_format($courseCreditPoints, 2) }} credits</button>
                    </form>
                @endif
            </div>
        </section>
    @endif

    <x-common.component-card :title="$attempt->courseAssessment->material->title" desc="Review the selected and correct answers.">
        <div class="space-y-4">
            @foreach($attempt->courseAssessment->questions as $question)
                @php($answer = $attempt->answers->firstWhere('course_assessment_question_id', $question->id))
                <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex justify-between gap-3"><h3 class="font-medium text-gray-800 dark:text-white">{{ $loop->iteration }}. {{ $question->prompt }}</h3><x-ui.badge :color="$answer?->is_correct ? 'success' : 'error'">{{ $answer?->is_correct ? 'Correct' : 'Incorrect' }}</x-ui.badge></div>
                    <div class="mt-3 space-y-2 text-sm">@foreach($question->options as $option)<p class="rounded-lg px-3 py-2 {{ $option->is_correct ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : (in_array($option->id, $answer?->selected_option_ids ?? [], true) ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-gray-50 text-gray-600 dark:bg-white/[0.03] dark:text-gray-300') }}">{{ $option->is_correct ? 'Correct answer: ' : (in_array($option->id, $answer?->selected_option_ids ?? [], true) ? 'Your answer: ' : '') }}{{ $option->option_text }}</p>@endforeach</div>
                </article>
            @endforeach
        </div>
        <div class="mt-5 flex flex-wrap justify-end gap-3"><a href="{{ route('learning.courses.summary', $enrollment) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Return to course</a>@if(! $attempt->passed)<form method="POST" action="{{ route('learning.courses.materials.course-assessment.start', [$enrollment, $attempt->courseAssessment->material]) }}">@csrf<button class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white"><i class="bi bi-arrow-repeat" aria-hidden="true"></i><span>Retake assessment</span></button></form>@endif</div>
    </x-common.component-card>
</div>
@endsection
