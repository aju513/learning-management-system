@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb :pageTitle="$title"><x-slot:actions><a href="{{ route(\App\Support\PortalRoute::name('fiscal-years.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button form="fiscal-year-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Save changes</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card title="Fiscal-year settings"><form id="fiscal-year-form" method="POST" action="{{ route(\App\Support\PortalRoute::name('fiscal-years.update', $fiscalYear)) }}" class="space-y-6">@csrf @method('PUT') @include('pages.admin.fiscal-years._form')</form></x-common.component-card>
@endsection
