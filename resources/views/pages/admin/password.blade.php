@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Change Password" />
<form method="POST" action="{{ route('account.password.update') }}" class="max-w-2xl">@csrf @method('PUT')
<x-common.component-card title="Change password" desc="Use at least 12 characters with uppercase, lowercase, number, and symbol.">
    <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Current password</label><input name="current_password" type="password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
    <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">New password</label><input name="password" type="password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
    <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Confirm new password</label><input name="password_confirmation" type="password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
    <div class="flex justify-end"><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Update password</button></div>
</x-common.component-card></form>
@endsection
