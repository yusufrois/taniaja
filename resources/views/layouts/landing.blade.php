<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- =====================================================
         SEO — Edit langsung di sini untuk mengubah meta tag landing page.
         ===================================================== --}}
    <title>AgroFlow — Manajemen Greenhouse & Pertanian | LadangWohIjo</title>
    <meta name="description" content="Platform manajemen greenhouse dan pertanian untuk mengelola modal, musim tanam, jadwal HST, biaya, panen, penjualan, HPP, dan laba.">
    <meta name="keywords" content="aplikasi manajemen greenhouse, software pertanian Indonesia, manajemen musim tanam, jadwal HST pertanian, hitung HPP pertanian, laba kebun, pencatatan biaya budidaya, aplikasi kebun greenhouse, platform pertanian digital, manajemen panen greenhouse, sistem manajemen lahan pertanian, aplikasi budidaya tanaman, modal pertanian, hutang petani, biaya operasional kebun, penjualan hasil panen, laporan keuangan pertanian, AgroFlow, LadangWohIjo, manajemen greenhouse Indonesia, software kebun modern, digital farming Indonesia, agritech Indonesia, greenhouse management system, farm management software, crop management system, HST scheduler, HPP calculator farm, pertanian, petani milenial, tanam cabe, tanam melon">
    <meta name="author" content="Taniaja">
    <meta name="robots" content="index, follow">

    {{-- Open Graph / Social Media --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="AgroFlow — Manajemen Greenhouse & Pertanian">
    <meta property="og:description" content="Kelola kebun dengan data anda. Hitung hasilnya. Platform manajemen greenhouse untuk mencatat modal, biaya, panen, penjualan, dan laba.">
    <meta property="og:image" content="{{ asset('images/og-landing.png') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AgroFlow">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="AgroFlow — Manajemen Greenhouse & Pertanian">
    <meta name="twitter:description" content="Kelola kebun dengan data. Hitung hasilnya. Platform manajemen greenhouse untuk mencatat modal, biaya, panen, penjualan, dan laba.">
    <meta name="twitter:image" content="{{ asset('images/og-landing.png') }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

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
