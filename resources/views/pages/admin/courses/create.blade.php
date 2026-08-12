@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Course" />
<x-common.component-card title="Course details" desc="The course starts as a draft. Add curriculum before publishing."><form method="POST" action="{{ route(\App\Support\PortalRoute::name('courses.store')) }}" enctype="multipart/form-data" class="space-y-6">@csrf @include('pages.admin.courses._form', ['submitLabel' => 'Create course'])</form></x-common.component-card>
@endsection
