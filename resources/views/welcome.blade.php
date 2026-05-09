<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="PortoHub adalah platform dokumentasi dan validasi portfolio proyek akhir siswa RPL.">

    <title>{{ config('app.name', 'PortoHub') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700;manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $projects = $projects ?? collect();
        $featuredProject = $featuredProject ?? null;
    @endphp

    <div id="top">
        @include('landing.partials.header')

        <main>
            @include('landing.partials.hero')
            @include('landing.partials.platform')
            @include('landing.partials.features')
            @include('landing.partials.testimonials')
            @include('landing.partials.cta')
        </main>

        @include('landing.partials.footer')
    </div>
</body>
</html>
