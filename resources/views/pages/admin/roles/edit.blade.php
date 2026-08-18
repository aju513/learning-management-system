@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Edit Role"><x-slot:actions><a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="role-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Save changes</button></x-slot:actions></x-common.page-breadcrumb>
<form id="role-form" method="POST" action="{{ route('admin.roles.update', $role) }}" class="w-full">
    @csrf
    @method('PUT')
    <x-common.component-card title="Edit role" desc="Update the permissions assigned to this role.">
        @include('pages.admin.roles._form', ['submitLabel' => 'Save changes'])
    </x-common.component-card>
</form>
@endsection
