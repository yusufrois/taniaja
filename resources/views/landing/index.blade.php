@extends('layouts.landing')

{{-- SEO — Ubah nilai di bawah ini sesuai konten final --}}
@section('title', 'AgroFlow — Manajemen Greenhouse & Pertanian | LadangWohIjo')
@section('description', 'Platform manajemen greenhouse dan pertanian untuk mengelola modal, musim tanam, jadwal HST, biaya, panen, penjualan, HPP, dan laba. Dikembangkan oleh LadangWohIjo.')
@section('keywords', 'greenhouse, manajemen pertanian, HST, hari setelah tanam, musim tanam, HPP, harga pokok produksi, AgroFlow, LadangWohIjo')
@section('og_title', 'AgroFlow — Manajemen Greenhouse & Pertanian')
@section('og_description', 'Kelola kebun dengan data. Hitung hasilnya. Platform manajemen greenhouse untuk mencatat modal, biaya, panen, penjualan, dan laba.')

@section('content')
    @include('landing.partials._header')
    @include('landing.partials._content')
    @include('landing.partials._footer')
@endsection
