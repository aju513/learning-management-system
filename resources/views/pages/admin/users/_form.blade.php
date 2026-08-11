<div class="grid gap-5 sm:grid-cols-2">
    <x-form.input name="name" label="Name" :value="$user->name" required />
    <x-form.input name="email" label="Email" type="email" :value="$user->email" required autocomplete="email" />
    <x-form.input name="password" label="Password" type="password" :help="$user->exists ? 'Leave blank to keep the current password.' : null" :required="!$user->exists" autocomplete="new-password" />
    <x-form.input name="password_confirmation" label="Confirm password" type="password" :required="!$user->exists" autocomplete="new-password" />
    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$user->status?->value ?? 'active'" required />
</div>
@can('users.assign-roles')
    <fieldset>
        <legend class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-400">Roles</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($roles as $role)
                <x-form.checkbox name="roles[]" :value="$role->name" :checked="in_array($role->name, old('roles', $user->exists ? $user->getRoleNames()->all() : []), true)" :label="$role->name" container-class="rounded-lg border border-gray-200 p-3 dark:border-gray-800" />
            @endforeach
        </div>
    </fieldset>
@endcan
<div class="flex justify-end gap-3">
    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a>
    <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button>
</div>
