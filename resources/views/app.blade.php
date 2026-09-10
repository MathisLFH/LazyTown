<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
     <footer class=" border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-8">

            <div class="flex flex-col md:flex-row justify-between items-center gap-4">

                <p class="text-sm text-gray-500">
                    © 2026 Lazytown
                </p>

                <nav class="flex gap-6 text-sm">
                    <a href="{{ route('aboutus') }}" class="text-gray-500 hover:text-gray-900">
                Über uns
                </a>

                    <a href="{{route('kontakt')}}" class="text-gray-500 hover:text-gray-900">
                        Kontakt
                    </a>

                    <a href="{{ route('impressum') }}" class="text-gray-500 hover:text-gray-900">
                        Impressum
                    </a>

                    <a href="{{ route('datenschutzerklaerung') }}" class="text-gray-500 hover:text-gray-900">
                        Datenschutz
                    </a>
                </nav>

            </div>

        </div>
    </footer>
</html>
