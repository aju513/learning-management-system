@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$title"><x-slot:actions><a href="{{ route($routeBase.'.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Close</a><button type="submit" form="account-form" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $user->exists ? 'Save changes' : 'Create account' }}</button></x-slot:actions></x-common.page-breadcrumb>
<x-common.component-card :title="$title" :desc="'This account will use the fixed '.$role->label().' portal and permissions.'">
    <form id="account-form" method="POST" action="{{ $user->exists ? route($routeBase.'.update', $user) : route($routeBase.'.store') }}" class="space-y-5">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Name" :value="$user->name" required />
            <x-form.input name="email" label="Email" type="email" :value="$user->email" required autocomplete="email" />
            <x-form.input name="password" label="Password" type="password" :help="$user->exists ? 'Leave blank to keep the current password.' : null" :required="! $user->exists" autocomplete="new-password" />
            <x-form.input name="password_confirmation" label="Confirm password" type="password" :required="! $user->exists" autocomplete="new-password" />
            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$user->status?->value ?? 'active'" required />
            <div><p class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">Portal role</p><div class="h-11 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ $role->label() }}</div></div>
        </div>
    </form>
</x-common.component-card>
@endsection
