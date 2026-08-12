@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Course" />
<x-common.component-card title="Course details"><form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.update'), $course) }}" enctype="multipart/form-data" class="space-y-6">@csrf @method('PUT') @include('pages.admin.courses._form', ['submitLabel' => 'Save changes'])</form></x-common.component-card>
@endsection
