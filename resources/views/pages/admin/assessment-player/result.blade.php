@extends(request()->routeIs('learning.*') ? 'layouts.assessment' : 'layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Assessment Result" />
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-2xl border p-8 {{ $attempt->passed ? 'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' : 'border-error-200 bg-error-50 dark:border-error-500/30 dark:bg-error-500/10' }}"><p class="text-sm font-semibold uppercase tracking-wide {{ $attempt->passed ? 'text-success-600' : 'text-error-600' }}">{{ $attempt->passed ? 'Passed' : 'Not passed' }}</p><div class="mt-3 flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-5xl font-bold text-gray-900 dark:text-white">{{ number_format((float) $attempt->score_percentage, 0) }}%</h1><p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $attempt->earned_marks }} / {{ $attempt->total_marks }} marks · Attempt {{ $attempt->attempt_number }}</p></div><span class="rounded-full bg-white/70 px-4 py-2 text-sm font-semibold">Passing score {{ $attempt->assessment->passing_percentage }}%</span></div></section>
    @if(($creditAward ?? null) && $attempt->passed)
        <section class="rounded-2xl border border-brand-200 bg-brand-50 p-6 dark:border-brand-500/30 dark:bg-brand-500/10"><div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-sm font-semibold uppercase text-brand-700">Test credit score</p><p class="mt-1 text-2xl font-bold">+{{ number_format((float) $creditAward->points, 2) }} credits</p><p class="mt-1 text-sm">{{ $creditAward->isClaimed() ? 'Credit claimed.' : 'Your passed-test credit is ready to claim.' }}</p></div>@if(! $creditAward->isClaimed())<form method="POST" action="{{ route('learning.credit-scores.claim', $creditAward) }}">@csrf<button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white">Claim credits</button></form>@endif</div></section>
    @endif
    <x-common.component-card :title="$attempt->assessment->title" desc="Review your submitted answers and grading feedback.">
        <div class="space-y-4">
            @foreach($attempt->assessment->questions as $question)
                @php($answer = $attempt->answers->firstWhere('assessment_question_id', $question->id))
                <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"><div class="flex justify-between gap-3"><h3 class="font-medium text-gray-800 dark:text-white">{{ $loop->iteration }}. {{ $question->prompt }}</h3><x-ui.badge :color="$answer?->is_correct ? 'success' : 'error'">{{ $answer?->is_correct ? 'Correct' : 'Incorrect' }}</x-ui.badge></div><p class="mt-2 text-sm text-gray-500">Earned {{ $answer?->earned_marks ?? 0 }} / {{ $question->marks }} marks</p>
                    @if($question->type === \App\Enums\QuestionType::QuestionAnswer)
                        <div class="mt-3 space-y-2 text-sm"><p class="rounded-lg bg-gray-50 p-3 dark:bg-white/[0.03]"><strong>Your answer:</strong> {{ $answer?->text_answer ?: 'No answer' }}</p>@if($answer?->reviewer_feedback)<p class="rounded-lg bg-brand-50 p-3 text-brand-800 dark:bg-brand-500/10 dark:text-brand-200"><strong>Reviewer feedback:</strong> {{ $answer->reviewer_feedback }}</p>@endif</div>
                    @else
                        <div class="mt-3 space-y-2 text-sm">@foreach($question->options as $option)@php($selected = in_array($option->id, $answer?->selected_option_ids ?? [], true))<p class="rounded-lg px-3 py-2 {{ $option->is_correct ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : ($selected ? 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400' : 'bg-gray-50 text-gray-600 dark:bg-white/[0.03] dark:text-gray-300') }}">{{ $option->is_correct ? 'Correct answer: ' : ($selected ? 'Your answer: ' : '') }}{{ $option->option_text }}</p>@endforeach</div>
                    @endif
                </article>
            @endforeach
        </div>
        @if(request()->routeIs('learning.*'))<div class="mt-6 flex flex-wrap justify-end gap-3"><a href="{{ route('learning.assessments.show', $attempt->assessment) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700">Test overview</a>@if(! $attempt->passed)<a href="{{ route('learning.assessments.show', $attempt->assessment) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Check retry availability</a>@endif</div>@endif
    </x-common.component-card>
</div>
@endsection
