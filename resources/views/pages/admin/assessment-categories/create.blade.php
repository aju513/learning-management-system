@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Test Category"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('assessment-categories.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="assessment-category-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Create category</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Category details" desc="Categories keep the test catalog organized.">
    <form id="assessment-category-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('assessment-categories.store')) }}">@csrf @include('pages.admin.assessment-categories._form')</form>
</x-common.component-card>
@endsection
