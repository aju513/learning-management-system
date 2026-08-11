@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Create User" />
<form method="POST" action="{{ route('admin.users.store') }}" class="w-full">
    @csrf
    <x-common.component-card title="Create user" desc="Create an account, set its status, and assign roles.">
        @include('pages.admin.users._form', ['submitLabel' => 'Create user'])
    </x-common.component-card>
</form>
@endsection
