<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="name">Role name</label>
    <input id="name" name="name" value="{{ old('name', $role->name) }}" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
</div>
@foreach($permissionGroups as $group => $permissions)
    <fieldset class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <legend class="px-2 text-sm font-semibold capitalize text-gray-700 dark:text-gray-300">{{ str_replace('-', ' ', $group) }}</legend>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($permissions as $permission)
                <label class="flex items-start gap-2 rounded-lg border border-gray-100 p-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">
                    <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" @checked(in_array($permission['name'], old('permissions', $role->exists ? $role->getPermissionNames()->all() : []), true)) class="mt-0.5 rounded border-gray-300 text-brand-500">
                    <span><span class="block font-medium text-gray-800 dark:text-gray-200">{{ $permission['view_title'] }}</span><span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $permission['description'] }}</span><span class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500">{{ $permission['name'] }}</span></span>
                </label>
            @endforeach
        </div>
    </fieldset>
@endforeach
<div class="flex justify-end gap-3">
    <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a>
    <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button>
</div>
