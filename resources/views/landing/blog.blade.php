@extends('layouts.landing')

@section('content')

@include('landing.partials._navbar')

{{-- Page Hero --}}
<section class="page-hero">
    <div class="container">
        <div class="eyebrow">Blog &amp; Artikel</div>
        <h1>Insights dari <span style="color:var(--grass2)">lapangan pertanian.</span></h1>
        <p>Tips, panduan, dan wawasan seputar manajemen greenhouse, budidaya, dan pertanian digital Indonesia.</p>
    </div>
</section>

{{-- Blog Grid --}}
<section class="section">
    <div class="container">
        <div class="blog-grid">

            {{-- Artikel 1 --}}
            <article class="blog-card">
                <div class="soon-badge">Segera Hadir</div>
                <div class="blog-thumb">📊</div>
                <div class="blog-body">
                    <span class="blog-tag">Keuangan</span>
                    <h3>Cara Menghitung HPP Pertanian Greenhouse dengan Tepat</h3>
                    <p>Harga Pokok Produksi (HPP) adalah angka paling krusial dalam menilai profitabilitas musim tanam. Pelajari cara menghitungnya secara akurat.</p>
                    <div class="blog-meta">
                        <span>LadangWohIjo</span>
                        <span>Segera hadir</span>
                    </div>
                </div>
            </article>

            {{-- Artikel 2 --}}
            <article class="blog-card">
                <div class="soon-badge">Segera Hadir</div>
                <div class="blog-thumb">📅</div>
                <div class="blog-body">
                    <span class="blog-tag">Budidaya</span>
                    <h3>Panduan Jadwal HST Melon: Dari Tanam Hingga Panen</h3>
                    <p>Jadwal berbasis Hari Setelah Tanam (HST) adalah kunci konsistensi budidaya. Simak panduan lengkap untuk komoditas melon greenhouse.</p>
                    <div class="blog-meta">
                        <span>LadangWohIjo</span>
                        <span>Segera hadir</span>
                    </div>
                </div>
            </article>

            {{-- Artikel 3 --}}
            <article class="blog-card">
                <div class="soon-badge">Segera Hadir</div>
                <div class="blog-thumb">💰</div>
                <div class="blog-body">
                    <span class="blog-tag">Bisnis</span>
                    <h3>Tips Manajemen Modal Kebun Greenhouse yang Efisien</h3>
                    <p>Memisahkan modal pembangunan dari biaya operasional musim adalah langkah pertama menuju pembukuan kebun yang sehat dan dapat diandalkan.</p>
                    <div class="blog-meta">
                        <span>LadangWohIjo</span>
                        <span>Segera hadir</span>
                    </div>
                </div>
            </article>

            {{-- Artikel 4 --}}
            <article class="blog-card">
                <div class="soon-badge">Segera Hadir</div>
                <div class="blog-thumb">🏡</div>
                <div class="blog-body">
                    <span class="blog-tag">Teknis</span>
                    <h3>Mengenal SOP Budidaya Greenhouse Modern di Indonesia</h3>
                    <p>SOP yang baik adalah fondasi konsistensi hasil produksi. Pelajari bagaimana greenhouse modern menyusun dan menjalankan SOP budidayanya.</p>
                    <div class="blog-meta">
                        <span>LadangWohIjo</span>
                        <span>Segera hadir</span>
                    </div>
                </div>
            </article>

            {{-- Artikel 5 --}}
            <article class="blog-card">
                <div class="soon-badge">Segera Hadir</div>
                <div class="blog-thumb">🌱</div>
                <div class="blog-body">
                    <span class="blog-tag">Budidaya</span>
                    <h3>Manajemen Nutrisi Hidroponik: Panduan EC &amp; pH untuk Pemula</h3>
                    <p>EC dan pH adalah dua parameter paling vital dalam sistem hidroponik. Pahami cara memantau dan menyesuaikannya agar tanaman tumbuh optimal.</p>
                    <div class="blog-meta">
                        <span>LadangWohIjo</span>
                        <span>Segera hadir</span>
                    </div>
                </div>
            </article>

            {{-- Artikel 6 --}}
            <article class="blog-card">
                <div class="soon-badge">Segera Hadir</div>
                <div class="blog-thumb">📈</div>
                <div class="blog-body">
                    <span class="blog-tag">Agritech</span>
                    <h3>Digital Farming Indonesia: Tren dan Peluang di 2025–2026</h3>
                    <p>Pertanian digital bukan lagi tren masa depan — ia sudah hadir hari ini. Simak lanskap agritech Indonesia dan bagaimana petani modern bersiap menghadapinya.</p>
                    <div class="blog-meta">
                        <span>LadangWohIjo</span>
                        <span>Segera hadir</span>
                    </div>
                </div>
            </article>

        </div>

        {{-- Newsletter CTA --}}
        <div style="margin-top:60px;text-align:center;padding:48px 24px;border:1px solid var(--line);border-radius:22px;background:radial-gradient(circle at 50% 0%,rgba(120,168,90,.08),transparent 50%)">
            <div class="eyebrow">Jangan lewatkan artikel terbaru</div>
            <h2 style="font-size:clamp(24px,3vw,38px);margin:0 auto 12px;max-width:560px">Artikel pertama segera terbit. Pantau terus perkembangannya.</h2>
            <p style="color:var(--muted);margin-bottom:28px">Kami sedang menyiapkan konten berkualitas seputar manajemen greenhouse dan pertanian digital.</p>
            <a href="{{ route('landing.kontak') }}" class="btn btn-primary">Hubungi Kami →</a>
        </div>
    </div>
</section>

@include('landing.partials._footer')

@endsection
