<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="password">Password {{ $user->exists ? '(leave blank to keep)' : '' }}</label>
        <input id="password" name="password" type="password" @required(!$user->exists) class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(!$user->exists) class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="status">Status</label>
        <select id="status" name="status" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <option value="active" @selected(old('status', $user->status?->value ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $user->status?->value) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
@can('users.assign-roles')
    <fieldset>
        <legend class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-400">Roles</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($roles as $role)
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 text-sm text-gray-700 dark:border-gray-800 dark:text-gray-300">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', $user->exists ? $user->getRoleNames()->all() : []), true)) class="rounded border-gray-300 text-brand-500">
                    {{ $role->name }}
                </label>
            @endforeach
        </div>
    </fieldset>
@endcan
<div class="flex justify-end gap-3">
    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a>
    <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button>
</div>
