@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="My Profile" />
<form method="POST" action="{{ route('admin.profile.update') }}" class="max-w-2xl">@csrf @method('PUT')
<x-common.component-card title="Profile information" desc="Update the name and email shown throughout the admin area.">
    <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Name</label><input name="name" value="{{ old('name', auth()->user()->name) }}" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
    <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label><input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
    <div class="flex justify-end"><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Save profile</button></div>
</x-common.component-card></form>
@endsection
