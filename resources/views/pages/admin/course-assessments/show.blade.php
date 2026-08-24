@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$assessment->material->title"><x-slot:actions>
    <a href="{{ route(\App\Support\PortalRoute::name('courses.show'), $assessment->material->chapter->module->course) }}#chapter-{{ $assessment->material->chapter->id }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Back to course</a>
</x-slot:actions></x-common.page-breadcrumb>
@php($questionsLocked = (int) ($assessment->started_attempts ?? 0) > 0)
<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <div class="space-y-6">
        <x-common.component-card title="Multiple-choice questions" desc="Course assessments are separate from standalone quizzes. Questions can be single-choice or multiple-choice.">
            <div class="space-y-4">
                @forelse($assessment->questions as $question)
                    <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs text-gray-500">Question {{ $loop->iteration }} · {{ $question->type->label() }} · {{ $question->marks }} marks</p><h3 class="mt-1 font-medium text-gray-800 dark:text-white">{{ $question->prompt }}</h3></div>
                            @if(! $questionsLocked)<div class="flex gap-2"><a href="{{ route(\App\Support\PortalRoute::name('course-assessment-questions.edit'), $question) }}" class="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:text-white">Edit</a><form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-assessment-questions.destroy'), $question) }}" onsubmit="return confirm('Delete this question?')">@csrf @method('DELETE')<button class="rounded bg-error-50 px-2 py-1 text-xs text-error-600">Delete</button></form></div>@endif
                        </div>
                        <ul class="mt-3 grid gap-2 sm:grid-cols-2">@foreach($question->options as $option)<li class="rounded-lg px-3 py-2 text-sm {{ $option->is_correct ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-50 text-gray-600 dark:bg-white/[0.03] dark:text-gray-300' }}">{{ $option->is_correct ? '✓' : '○' }} {{ $option->option_text }}</li>@endforeach</ul>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">No questions yet. Add at least one before publishing the course.</p>
                @endforelse
            </div>
        </x-common.component-card>
        @if(! $questionsLocked)
            <x-common.component-card title="Add question" desc="Add the required questions. Use multiple choice when more than one answer is correct.">
                <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-assessment-questions.store'), $assessment) }}" class="space-y-6">@csrf @include('pages.admin.course-assessments._question-form', ['question' => null])<div class="text-right"><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Add question</button></div></form>
            </x-common.component-card>
        @else
            <p class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700">Questions are locked because trainees have already started this assessment.</p>
        @endif
    </div>
    <aside><x-common.component-card title="Assessment settings"><dl class="space-y-3 text-sm"><div class="flex justify-between"><dt class="text-gray-500">Passing score</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->passing_percentage }}%</dd></div><div class="flex justify-between"><dt class="text-gray-500">Questions</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->questions->count() }} / {{ config('lms.course_assessment_min_questions', 10) }}</dd></div><div class="flex justify-between"><dt class="text-gray-500">Attempts</dt><dd class="font-medium text-gray-800 dark:text-white">Unlimited</dd></div><div class="flex justify-between"><dt class="text-gray-500">Started attempts</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $assessment->started_attempts ?? 0 }}</dd></div></dl><p class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-500 dark:border-gray-800">After submission, trainees see their selected and correct answers. A passing result completes this learning material.</p></x-common.component-card></aside>
</div>
@endsection
