<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __($title ?? 'Learning') }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
</head>

<body class="min-h-full bg-gray-50 text-gray-800 dark:bg-gray-950 dark:text-white">
    <div class="min-h-screen">
        @include('layouts.trainee.header')

        <main class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-success-500/30 bg-success-50 px-4 py-3 text-sm text-success-700 dark:bg-success-500/15 dark:text-success-400">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-error-500/30 bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>
