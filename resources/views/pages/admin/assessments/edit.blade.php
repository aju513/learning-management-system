@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Assessment" />
<x-common.component-card title="Assessment settings"><form method="POST" action="{{ route(\App\Support\PortalRoute::name('assessments.update'), $assessment) }}" class="space-y-6">@csrf @method('PUT') @include('pages.admin.assessments._form', ['submitLabel' => 'Save changes'])</form></x-common.component-card>
@endsection
