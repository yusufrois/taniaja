<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- =====================================================
         SEO — Isi nilai default di bawah ini sesuai kebutuhan.
         Override per-halaman dengan @section('title', '...') dsb.
         ===================================================== --}}
    <title>@yield('title', 'AgroFlow — Manajemen Greenhouse & Pertanian | LadangWohIjo')</title>
    <meta name="description" content="@yield('description', 'Platform manajemen greenhouse dan pertanian untuk mengelola modal, musim tanam, jadwal HST, biaya, panen, penjualan, HPP, dan laba. Dikembangkan oleh LadangWohIjo.')">
    <meta name="keywords" content="@yield('keywords', 'greenhouse, manajemen pertanian, HST, musim tanam, HPP, AgroFlow, LadangWohIjo')">
    <meta name="author" content="LadangWohIjo">
    <meta name="robots" content="@yield('robots', 'index, follow')">

    {{-- Open Graph / Social Media --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'AgroFlow — Manajemen Greenhouse & Pertanian')">
    <meta property="og:description" content="@yield('og_description', 'Platform manajemen greenhouse dan pertanian untuk mengelola modal, musim tanam, jadwal HST, biaya, panen, penjualan, HPP, dan laba.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-landing.png'))">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AgroFlow">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'AgroFlow — Manajemen Greenhouse & Pertanian')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Platform manajemen greenhouse dan pertanian untuk mengelola modal, musim tanam, jadwal HST, biaya, panen, penjualan, HPP, dan laba.')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/og-landing.png'))">

    {{-- Canonical URL --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- Favicon (ganti file sesuai asset project) --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Stylesheet Landing Page --}}
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">

    {{-- Slot CSS tambahan per-halaman jika dibutuhkan --}}
    @stack('styles')
</head>
<body>

    @yield('content')

    {{-- Slot JS tambahan per-halaman jika dibutuhkan --}}
    @stack('scripts')

</body>
</html>
