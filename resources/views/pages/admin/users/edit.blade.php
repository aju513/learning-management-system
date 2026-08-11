@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Edit User" />
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="w-full">
    @csrf
    @method('PUT')
    <x-common.component-card title="Edit user" desc="Update account identity, status, password, and assigned roles.">
        @include('pages.admin.users._form', ['submitLabel' => 'Save changes'])
    </x-common.component-card>
</form>
@endsection
