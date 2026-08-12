@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Course Category" />
<x-common.component-card title="Category details" desc="Categories keep the course catalog organized.">
    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-categories.store')) }}">@csrf @include('pages.admin.course-categories._form', ['submitLabel' => 'Create category'])</form>
</x-common.component-card>
@endsection
