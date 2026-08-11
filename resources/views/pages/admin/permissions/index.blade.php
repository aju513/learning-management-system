@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Permissions" />
<x-ui.alert variant="info" title="Code-owned catalog" message="Permissions are maintained in config/permissions.php. Run php artisan admin:permissions-sync after changes. The display text and descriptions below help administrators understand what each permission grants; this screen does not edit the catalog." />
<div class="mt-6 grid gap-5 lg:grid-cols-2">
    @foreach($permissionGroups as $group => $permissions)
        <x-common.component-card :title="ucfirst($group)">
            <div class="space-y-3">
                @foreach($permissions as $permission)
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-medium text-gray-800 dark:text-white">{{ $permission['view_title'] }}</h3>
                            <x-ui.badge color="primary">{{ $permission['name'] }}</x-ui.badge>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $permission['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-common.component-card>
    @endforeach
</div>
@endsection
