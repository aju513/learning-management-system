@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Question" />
<x-common.component-card :title="$question->assessment->title"><form method="POST" action="{{ route(\App\Support\PortalRoute::name('assessment-questions.update'), $question) }}" class="space-y-6">@csrf @method('PUT') @include('pages.admin.assessments._question-form')<div class="flex justify-end gap-3"><a href="{{ route(\App\Support\PortalRoute::name('assessments.show'), $question->assessment) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm text-white">Save question</button></div></form></x-common.component-card>
@endsection
