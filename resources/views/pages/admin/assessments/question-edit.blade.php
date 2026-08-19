@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Question"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('assessments.show'), $question->assessment) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="assessment-question-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Save question</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card :title="$question->assessment->title"><form id="assessment-question-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('assessment-questions.update'), $question) }}" class="space-y-6">@csrf @method('PUT') @include('pages.admin.assessments._question-form')</form></x-common.component-card>
@endsection
