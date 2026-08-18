@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Assessment"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('assessments.show'), $assessment) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="assessment-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Save changes</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Assessment settings"><form id="assessment-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('assessments.update'), $assessment) }}" class="space-y-6">@csrf @method('PUT') @include('pages.admin.assessments._form', ['submitLabel' => 'Save changes'])</form></x-common.component-card>
@endsection
