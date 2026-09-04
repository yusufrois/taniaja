{{-- ============================================================
     Partial: _navbar
     Navbar bersama — dipakai di Landing, Tentang, Blog, Kontak.
     ============================================================ --}}

<nav class="nav">
    <div class="container nav-inner">
        <a class="logo" href="{{ route('landing') }}">
            <div class="logo-mark">🌱</div>
            <div>AgroFlow<small>by LadangWohIjo</small></div>
        </a>
        <div class="nav-links">
            <a href="{{ route('landing') }}" @class(['active' => request()->routeIs('landing')])>Beranda</a>
            <a href="{{ route('landing') }}#fitur">Fitur</a>
            <a href="{{ route('landing.tentang') }}" @class(['active' => request()->routeIs('landing.tentang')])>Tentang</a>
            <a href="{{ route('landing.blog') }}" @class(['active' => request()->routeIs('landing.blog')])>Blog</a>
            <a href="{{ route('landing.kontak') }}" @class(['active' => request()->routeIs('landing.kontak')])>Kontak</a>
        </div>
        <a href="{{ route('login') }}" class="btn btn-primary">Masuk ke Sistem →</a>
    </div>
</nav>
