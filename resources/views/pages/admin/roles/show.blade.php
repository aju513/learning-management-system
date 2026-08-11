@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Role Details">
    <x-slot:actions>
        @if($role->name !== 'super-admin')
            @can('roles.edit')
                <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Edit role</a>
            @endcan
            @can('roles.delete')
                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg bg-error-50 px-4 py-2.5 text-sm font-medium text-error-600 dark:bg-error-500/10">Delete role</button>
                </form>
            @endcan
        @endif
    </x-slot:actions>
</x-common.page-breadcrumb>
<x-common.component-card title="{{ $role->name }}" desc="Role permissions and assigned users.">
    <div class="grid gap-5 sm:grid-cols-2">
        <div><p class="text-sm text-gray-500">Users</p><p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white">{{ $role->users_count }}</p></div>
        <div><p class="text-sm text-gray-500">Permissions</p><p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white">{{ $role->permissions->count() }}</p></div>
    </div>
    <div>
        <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-400">Permissions</h3>
        <div class="flex flex-wrap gap-2">
            @forelse($role->permissions as $permission)
                <x-ui.badge color="primary">{{ $permission->name }}</x-ui.badge>
            @empty
                <span class="text-sm text-gray-500">No permissions assigned.</span>
            @endforelse
        </div>
    </div>
    <div>
        <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-400">Assigned users</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($role->users as $user)
                        <tr><td class="px-4 py-3">@can('users.show')<a href="{{ route('admin.users.show', $user) }}" class="font-medium text-brand-500">{{ $user->name }}</a>@else<span class="font-medium text-gray-800 dark:text-white">{{ $user->name }}</span>@endcan</td><td class="px-4 py-3 text-sm text-gray-500">{{ $user->email }}</td><td class="px-4 py-3"><x-ui.badge :color="$user->isActive() ? 'success' : 'error'">{{ ucfirst($user->status->value) }}</x-ui.badge></td></tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No users are assigned to this role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-common.component-card>
@endsection
