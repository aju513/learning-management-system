@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Create User"><x-slot:actions><a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="user-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Create user</button></x-slot:actions></x-common.page-breadcrumb>
<form id="user-form" method="POST" action="{{ route('admin.users.store') }}" class="w-full">
    @csrf
    <x-common.component-card title="Create user" desc="Create an account, set its status, and assign roles.">
        @include('pages.admin.users._form', ['submitLabel' => 'Create user'])
    </x-common.component-card>
</form>
@endsection
