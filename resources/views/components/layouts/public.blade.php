<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription ?? 'PortoHub adalah platform dokumentasi dan validasi portfolio proyek akhir siswa RPL.' }}">

    <title>{{ $title ?? config('app.name', 'PortoHub') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700;manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-[#F0F4F8] text-slate-900 selection:bg-blue-200 selection:text-blue-900 overflow-x-hidden" id="top">
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-md">
        Skip to main content
    </a>

    <!-- Navbar -->
    <livewire:layout.navigation />

    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    <!-- Footer -->
    @include('landing.partials.footer')

    @livewireScripts
</body>
</html>
