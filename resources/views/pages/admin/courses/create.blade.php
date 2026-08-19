@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Course"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('courses.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="course-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Create course</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Course details" desc="The course starts as a draft. Add curriculum before publishing."><form id="course-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.store')) }}" enctype="multipart/form-data" class="space-y-6">@csrf @include('pages.admin.courses._form', ['submitLabel' => 'Create course'])</form></x-common.component-card>
@endsection
