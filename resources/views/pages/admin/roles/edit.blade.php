@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Edit Role" />
<form method="POST" action="{{ route('admin.roles.update', $role) }}" class="w-full">
    @csrf
    @method('PUT')
    <x-common.component-card title="Edit role" desc="Update the permissions assigned to this role.">
        @include('pages.admin.roles._form', ['submitLabel' => 'Save changes'])
    </x-common.component-card>
</form>
@endsection
