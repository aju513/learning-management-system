@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Create Role"><x-slot:actions><a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="role-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Create role</button></x-slot:actions></x-common.page-breadcrumb>
<form id="role-form" method="POST" action="{{ route('admin.roles.store') }}" class="w-full">
    @csrf
    <x-common.component-card title="Create role" desc="Assign only the permissions this role requires.">
        @include('pages.admin.roles._form', ['submitLabel' => 'Create role'])
    </x-common.component-card>
</form>
@endsection
