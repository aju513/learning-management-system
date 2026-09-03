<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __($title ?? 'Test') }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>(function(){const theme=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');if(theme==='dark')document.documentElement.classList.add('dark');})();</script>
</head>
<body class="min-h-full bg-gray-50 text-gray-800 dark:bg-gray-950 dark:text-white">
    <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-brand-500">Focused test session</p><p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ config('app.name') }}</p></div>
            <a href="{{ route('learning.assessments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-200">Exit to My Tests</a>
        </div>
    </header>
    <main class="mx-auto max-w-7xl p-4 sm:p-6">@yield('content')</main>
    @stack('scripts')
</body>
</html>
