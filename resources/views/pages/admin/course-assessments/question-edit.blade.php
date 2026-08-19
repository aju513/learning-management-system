@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Course Assessment Question"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('course-assessments.show'), $question->courseAssessment) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="course-assessment-question-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Save question</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card :title="$question->courseAssessment->material->title">
    <form id="course-assessment-question-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('course-assessment-questions.update'), $question) }}" class="space-y-6">@csrf @method('PUT') @include('pages.admin.course-assessments._question-form')</form>
</x-common.component-card>
@endsection
