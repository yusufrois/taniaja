{{-- ============================================================
     Partial: _header
     Berisi: Navbar + Hero section
     ============================================================ --}}

<nav class="nav">
    <div class="container nav-inner">
        <a class="logo" href="#">
            <div class="logo-mark">🌱</div>
            <div>AgroFlow<small>by LadangWohIjo</small></div>
        </a>
        <div class="nav-links">
            <a href="#fitur">Fitur</a>
            <a href="#alur">Alur Kerja</a>
            <a href="#hst">Jadwal HST</a>
            <a href="#keuangan">Keuangan</a>
            <a href="#roadmap">Roadmap</a>
        </div>
        <a href="#mulai" class="btn btn-primary">Pelajari Sistem →</a>
    </div>
</nav>

<header class="hero">
    <div class="container hero-grid">
        <div>
            <div class="badge"><span class="dot"></span> Platform manajemen greenhouse &amp; pertanian</div>
            <h1>Kelola kebun dengan data. <span>Hitung hasilnya.</span></h1>
            <p class="lead">AgroFlow membantu pemilik dan pengelola greenhouse mencatat modal, hutang, biaya budidaya, jadwal kegiatan berdasarkan HST, panen, penjualan, HPP hingga laba dalam satu alur yang saling terhubung.</p>
            <div class="hero-actions">
                <a href="#fitur" class="btn btn-primary">Lihat Fitur Lengkap →</a>
                <a href="#alur" class="btn btn-ghost">Lihat Cara Kerja</a>
            </div>
            <p class="hero-note">Dikembangkan oleh <strong>LadangWohIjo</strong> berdasarkan kebutuhan operasional budidaya greenhouse nyata.</p>
        </div>

        <div class="mockup">
            <div class="window">
                <div class="window-top">
                    <i></i><i></i><i></i>
                    <span style="margin-left:auto;font-size:9px;color:#667267">agroflow.local/dashboard</span>
                </div>
                <div class="dash">
                    <div class="dash-head">
                        <div>
                            <div class="dash-title">Dashboard Kebun</div>
                            <div class="dash-sub">Ringkasan seluruh unit &amp; musim</div>
                        </div>
                        <div class="select">Musim 2026 ▾</div>
                    </div>
                    <div class="cards">
                        <div class="card"><small>Modal</small><b>Rp225 jt</b><span class="up">↗ aktif</span></div>
                        <div class="card"><small>Biaya</small><b>Rp84 jt</b><span class="up">32 transaksi</span></div>
                        <div class="card"><small>Panen</small><b>1.250</b><span class="up">tanaman</span></div>
                        <div class="card"><small>Estimasi Laba</small><b>Rp46 jt</b><span class="up">+18,4%</span></div>
                    </div>
                    <div class="chart">
                        <span class="chart-label">Biaya vs Pendapatan per minggu</span>
                        <div class="chart-line"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:9px">
                        <div class="card"><small>Aktivitas Hari Ini</small><b style="font-size:12px">Pemupukan — HST 32</b></div>
                        <div class="card"><small>Status Musim</small><b style="font-size:12px;color:#91bc70">Budidaya Aktif</b></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
