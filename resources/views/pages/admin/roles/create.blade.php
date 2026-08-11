@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Create Role" />
<form method="POST" action="{{ route('admin.roles.store') }}" class="w-full">
    @csrf
    <x-common.component-card title="Create role" desc="Assign only the permissions this role requires.">
        @include('pages.admin.roles._form', ['submitLabel' => 'Create role'])
    </x-common.component-card>
</form>
@endsection
