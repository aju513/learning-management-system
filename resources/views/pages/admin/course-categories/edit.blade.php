@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Course Category"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('course-categories.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="course-category-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Save changes</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Category details">
    <form id="course-category-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('course-categories.update'), $category) }}">@csrf @method('PUT') @include('pages.admin.course-categories._form', ['submitLabel' => 'Save changes'])</form>
</x-common.component-card>
@endsection
