<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-slate-100">
        <div class="min-h-screen relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.16),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(2,132,199,0.14),_transparent_32%)]"></div>

            <div class="relative min-h-screen flex items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="w-full max-w-md">
                    <div class="flex justify-center mb-6">
                        <a href="/" wire:navigate class="inline-flex items-center gap-3 rounded-full bg-white/80 px-4 py-2 shadow-sm ring-1 ring-gray-200 backdrop-blur">
                            <x-application-logo class="h-8 w-8 fill-current text-blue-700" />
                            <span class="text-sm font-semibold tracking-wide text-gray-700">{{ config('app.name', 'Laravel') }}</span>
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200/80">
                        <div class="px-6 py-6 sm:px-8 sm:py-8">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
