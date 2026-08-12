@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Create Assessment" />
<x-common.component-card title="Assessment settings" desc="Create a standalone test or link it to a course or module."><form method="POST" action="{{ route(\App\Support\PortalRoute::name('assessments.store')) }}" class="space-y-6">@csrf @include('pages.admin.assessments._form', ['submitLabel' => 'Create assessment'])</form></x-common.component-card>
@endsection
