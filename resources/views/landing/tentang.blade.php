@extends('layouts.landing')

@section('content')

@include('landing.partials._navbar')

{{-- Page Hero --}}
<section class="page-hero">
    <div class="container">
        <div class="eyebrow">Tentang Kami</div>
        <h1>Dibangun dari lapangan. <span style="color:var(--grass2)">Untuk lapangan.</span></h1>
        <p>AgroFlow lahir dari pengalaman nyata mengelola greenhouse komersial — bukan dari asumsi di atas kertas.</p>
    </div>
</section>

{{-- Cerita Kami --}}
<section class="section">
    <div class="container">
        <div class="eyebrow">Cerita kami</div>
        <div class="about-lead">
            <div>
                <h2>Dari catatan Excel yang berantakan, menjadi sistem yang menjawab kebutuhan nyata.</h2>
            </div>
            <div>
                <p>LadangWohIjo memulai operasional greenhouse komersial dengan pencatatan manual — buku, spreadsheet, nota belanja, dan pesan WhatsApp yang tersebar. Setiap musim tanam selesai, kami kesulitan menjawab pertanyaan sederhana: <em>sebenarnya musim ini untung berapa?</em></p>
                <p>Dari masalah itu, AgroFlow dibangun. Bukan sebagai produk dari luar yang dipaksakan masuk ke lapangan, tapi sebagai solusi yang tumbuh dari dalam operasional kebun itu sendiri.</p>
                <p>Setiap fitur yang ada di AgroFlow mencerminkan satu masalah nyata yang pernah kami hadapi sendiri sebagai pengelola greenhouse.</p>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat">
                <div class="stat-num">3+</div>
                <div class="stat-label">Tahun pengalaman budidaya greenhouse</div>
            </div>
            <div class="stat">
                <div class="stat-num">9</div>
                <div class="stat-label">Modul manajemen dalam satu platform</div>
            </div>
            <div class="stat">
                <div class="stat-num">100%</div>
                <div class="stat-label">Dibangun dari kebutuhan lapangan nyata</div>
            </div>
        </div>
    </div>
</section>

{{-- Visi & Misi --}}
<section class="section alt">
    <div class="container">
        <div class="eyebrow">Visi &amp; Misi</div>
        <h2>Ke mana AgroFlow akan berkembang.</h2>
        <p class="section-intro">Kami percaya data adalah fondasi keputusan yang baik — dan petani Indonesia berhak mendapatkan tools yang membantu mereka berpikir seperti bisnis, bukan hanya bertani.</p>

        <div class="vision-grid">
            <div class="vision-card">
                <div class="icon">🎯</div>
                <h3>Visi</h3>
                <p>Menjadi platform manajemen pertanian digital terdepan di Indonesia yang membantu pengelola greenhouse dan petani modern mengambil keputusan berdasarkan data aktual, bukan intuisi semata.</p>
            </div>
            <div class="vision-card">
                <div class="icon">🚀</div>
                <h3>Misi</h3>
                <p>Menyederhanakan kompleksitas pencatatan budidaya menjadi alur data yang mudah diikuti — dari modal masuk, aktivitas harian, hasil panen, hingga laporan laba yang bisa ditelusuri ke setiap transaksinya.</p>
            </div>
        </div>
    </div>
</section>

{{-- Nilai-Nilai --}}
<section class="section">
    <div class="container">
        <div class="eyebrow">Nilai-nilai kami</div>
        <h2>Prinsip yang mendasari setiap keputusan pengembangan.</h2>

        <div class="value-grid">
            <div class="value-item">
                <h4>🌱 Berakar dari Lapangan</h4>
                <p>Setiap fitur divalidasi dari pengalaman operasional nyata, bukan asumsi produk.</p>
            </div>
            <div class="value-item">
                <h4>📊 Data Sebagai Bahasa</h4>
                <p>Kami percaya bahwa data yang terstruktur lebih berbicara daripada catatan yang tersebar.</p>
            </div>
            <div class="value-item">
                <h4>⚙️ Bertahap dan Terukur</h4>
                <p>Pengembangan dilakukan secara iteratif — fitur baru hadir berdasarkan kebutuhan nyata pengguna.</p>
            </div>
            <div class="value-item">
                <h4>🔒 Kepercayaan Data</h4>
                <p>Data bisnis Anda adalah milik Anda. Keamanan dan isolasi data adalah prioritas utama kami.</p>
            </div>
            <div class="value-item">
                <h4>🤝 Sederhana untuk Dipakai</h4>
                <p>Kompleksitas bisnis pertanian kami sederhanakan, bukan kami tambah. UI dirancang untuk pengguna non-teknis.</p>
            </div>
            <div class="value-item">
                <h4>📱 Siap di Lapangan</h4>
                <p>Bisa digunakan dari smartphone langsung di area greenhouse, tidak hanya dari kantor.</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta">
    <div class="container">
        <div class="cta-box">
            <div class="eyebrow">Bergabung bersama kami</div>
            <h2>Jadikan data kebun Anda bekerja lebih keras dari sebelumnya.</h2>
            <p>Mulai dengan mencatat satu musim tanam. Lihat bedanya saat panen tiba.</p>
            <a href="{{ route('login') }}" class="btn btn-primary">Masuk ke Sistem →</a>
        </div>
    </div>
</section>

@include('landing.partials._footer')

@endsection
