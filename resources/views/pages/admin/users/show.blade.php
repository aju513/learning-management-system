@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="User Details">
    <x-slot:actions>
        @can('users.edit')
            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Edit user</a>
        @endcan
        @can('users.delete')
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Permanently delete this user?')">
                @csrf
                @method('DELETE')
                <button class="rounded-lg bg-error-50 px-4 py-2.5 text-sm font-medium text-error-600 dark:bg-error-500/10">Delete user</button>
            </form>
        @endcan
    </x-slot:actions>
</x-common.page-breadcrumb>
<x-common.component-card title="{{ $user->name }}" desc="Account details and assigned access roles.">
    <dl class="grid gap-5 sm:grid-cols-2">
        <div><dt class="text-sm text-gray-500">Name</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $user->name }}</dd></div>
        <div><dt class="text-sm text-gray-500">Email</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $user->email }}</dd></div>
        <div><dt class="text-sm text-gray-500">Status</dt><dd class="mt-1"><x-ui.badge :color="$user->isActive() ? 'success' : 'error'">{{ ucfirst($user->status->value) }}</x-ui.badge></dd></div>
        <div><dt class="text-sm text-gray-500">Created</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white">{{ $user->created_at?->format('M j, Y H:i') }}</dd></div>
    </dl>
    <div>
        <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-400">Roles</h3>
        <div class="flex flex-wrap gap-2">
            @forelse($user->roles as $role)
                <x-ui.badge color="primary">{{ $role->name }}</x-ui.badge>
            @empty
                <span class="text-sm text-gray-500">No roles assigned.</span>
            @endforelse
        </div>
    </div>
</x-common.component-card>
@endsection
