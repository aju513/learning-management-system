@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Course Category" />
<x-common.component-card title="Category details">
    <form method="POST" action="{{ route(\App\Support\PortalRoute::name('course-categories.update'), $category) }}">@csrf @method('PUT') @include('pages.admin.course-categories._form', ['submitLabel' => 'Save changes'])</form>
</x-common.component-card>
@endsection
