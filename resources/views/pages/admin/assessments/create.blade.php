@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Assessment"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('assessments.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="assessment-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Create quiz</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Quiz settings" desc="Create a standalone quiz and assign it directly to trainees."><form id="assessment-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('assessments.store')) }}" class="space-y-6">@csrf @include('pages.admin.assessments._form', ['submitLabel' => 'Create quiz'])</form></x-common.component-card>
@endsection
