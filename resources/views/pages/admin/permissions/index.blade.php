@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Fixed Access Matrix" />
<x-ui.alert variant="info" title="Four code-owned roles" message="Portal roles and permissions are fixed in code. This screen is read-only; run php artisan admin:permissions-sync after configuration changes." />
<div class="mt-6 grid gap-5 xl:grid-cols-2">
    @foreach ($roleMatrices as $role => $permissions)
        <x-common.component-card :title="$role" :desc="count($permissions).' permissions'">
            <div class="flex max-h-64 flex-wrap gap-2 overflow-y-auto">
                @foreach ($permissions as $permission)<x-ui.badge color="light">{{ $permission }}</x-ui.badge>@endforeach
            </div>
        </x-common.component-card>
    @endforeach
</div>
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
