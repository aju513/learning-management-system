@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Roles">
    <x-slot:actions>
        @can('roles.create')<a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white"><x-common.menu-icon name="create" class="h-4 w-4" />Create role</a>@endcan
    </x-slot:actions>
</x-common.page-breadcrumb>
<x-common.component-card title="Role management" desc="Roles group the code-owned permission catalog.">
    <form method="GET" action="{{ route('admin.roles.index') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search role name" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        <button class="rounded-lg border border-gray-300 px-4 text-sm font-medium dark:border-gray-700 dark:text-white">Filter</button>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-4 py-3">S.N.</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Users</th><th class="px-4 py-3">Permissions</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($roles as $role)
                    <tr><td class="px-4 py-4 text-sm text-gray-500">{{ $roles->firstItem() + $loop->index }}</td><td class="px-4 py-4">@can('roles.show')<a href="{{ route('admin.roles.show', $role) }}" class="font-medium text-brand-500">{{ $role->name }}</a>@else<span class="font-medium text-gray-800 dark:text-white">{{ $role->name }}</span>@endcan</td><td class="px-4 py-4 text-sm text-gray-500">{{ $role->users_count }}</td><td class="px-4 py-4"><div class="flex flex-wrap gap-1">@foreach($role->permissions->take(5) as $permission)<x-ui.badge color="light">{{ $permission->name }}</x-ui.badge>@endforeach @if($role->permissions->count() > 5)<span class="text-xs text-gray-500">+{{ $role->permissions->count() - 5 }} more</span>@endif</div></td><td class="px-4 py-4"><div class="flex justify-end gap-2">@can('roles.show')<a href="{{ route('admin.roles.show', $role) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-brand-500 dark:border-gray-700" title="View role"><x-common.menu-icon name="view" class="h-4 w-4" />View</a>@endcan @if($role->name !== 'super-admin') @can('roles.edit')<a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium dark:border-gray-700 dark:text-white" title="Edit role"><x-common.menu-icon name="edit" class="h-4 w-4" />Edit</a>@endcan @can('roles.delete')<form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">@csrf @method('DELETE')<button class="inline-flex items-center gap-1 rounded-lg bg-error-50 px-3 py-2 text-xs font-medium text-error-600 dark:bg-error-500/10" title="Delete role"><x-common.menu-icon name="delete" class="h-4 w-4" />Delete</button></form>@endcan @else <x-ui.badge color="success">Protected</x-ui.badge> @endif</div></td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No roles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $roles->links() }}
</x-common.component-card>
@endsection
