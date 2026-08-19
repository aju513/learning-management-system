@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Course Category"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('course-categories.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="course-category-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Create category</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Category details" desc="Categories keep the course catalog organized.">
    <form id="course-category-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('course-categories.store')) }}">@csrf @include('pages.admin.course-categories._form', ['submitLabel' => 'Create category'])</form>
</x-common.component-card>
@endsection
