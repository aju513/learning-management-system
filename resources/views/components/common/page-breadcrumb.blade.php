@props(['pageTitle' => 'Page'])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="flex flex-col gap-1.5">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            {{ $pageTitle }}
        </h2>
        <nav aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5 whitespace-nowrap">
            <li>
                <a
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                    href="{{ url('/') }}"
                >
                    Home
                    <svg
                        class="stroke-current"
                        width="17"
                        height="16"
                        viewBox="0 0 17 16"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                            stroke=""
                            stroke-width="1.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </a>
            </li>
            <li class="text-sm text-gray-800 dark:text-white/90">
                {{ $pageTitle }}
            </li>
            </ol>
        </nav>
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
    </div>
