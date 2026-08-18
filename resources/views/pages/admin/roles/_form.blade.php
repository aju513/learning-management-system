<x-form.input name="name" label="Role name" :value="$role->name" required />
@foreach($permissionGroups as $group => $permissions)
    <fieldset class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <legend class="px-2 text-sm font-semibold capitalize text-gray-700 dark:text-gray-300">{{ str_replace('-', ' ', $group) }}</legend>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($permissions as $permission)
                <x-form.checkbox
                    name="permissions[]"
                    :value="$permission['name']"
                    :checked="in_array($permission['name'], old('permissions', $role->exists ? $role->getPermissionNames()->all() : []), true)"
                    :label="$permission['view_title']"
                    :description="$permission['description'].' ('.$permission['name'].')'"
                    container-class="rounded-lg border border-gray-100 p-3 text-gray-600 dark:border-gray-800 dark:text-gray-400"
                />
            @endforeach
        </div>
    </fieldset>
@endforeach
