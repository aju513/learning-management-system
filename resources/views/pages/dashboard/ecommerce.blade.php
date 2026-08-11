@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Dashboard" />
<div class="grid gap-6 md:grid-cols-3">
    <x-common.component-card title="Welcome back" class="md:col-span-2">
        <p class="text-sm text-gray-600 dark:text-gray-400">Signed in as <strong class="text-gray-800 dark:text-white">{{ auth()->user()->name }}</strong>. The administration foundation is ready for application modules.</p>
    </x-common.component-card>
    <x-common.component-card title="Your access">
        <div class="flex flex-wrap gap-2">@foreach(auth()->user()->roles as $role)<x-ui.badge color="primary">{{ $role->name }}</x-ui.badge>@endforeach</div>
    </x-common.component-card>
</div>
@endsection
