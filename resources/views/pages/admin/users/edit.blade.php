@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Edit User"><x-slot:actions><a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="user-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Save changes</button></x-slot:actions></x-common.page-breadcrumb>
<form id="user-form" method="POST" action="{{ route('admin.users.update', $user) }}" class="w-full">
    @csrf
    @method('PUT')
    <x-common.component-card title="Edit user" desc="Update account identity, status, password, and assigned roles.">
        @include('pages.admin.users._form', ['submitLabel' => 'Save changes'])
    </x-common.component-card>
</form>
@endsection
