# GEMINI.md — Index Konteks Proyek Taniaja (AgroFlow)

> File ini dibuat untuk mempercepat sesi pengembangan selanjutnya.
> Baca file ini di awal sesi agar tidak perlu menjelaskan ulang struktur yang sudah ada.

---

## Identitas Proyek

| Item | Nilai |
|------|-------|
| Nama App | AgroFlow |
| Brand | LadangWohIjo |
| Framework | Laravel 12 |
| Build Tool | Vite (bawaan Laravel) |
| CSS Utama (App) | Tailwind CSS v4 via `@tailwindcss/vite` |
| Auth | Livewire + session (bukan Sanctum untuk web UI) |
| URL Lokal | `http://taniaja.test/` atau `http://127.0.0.1:8000/` |

---

## Tiga Konsep Utama (Scope)

| # | Konsep | Status | Keterangan |
|---|--------|--------|------------|
| 1 | **Landing Page** | ✅ Selesai | Halaman publik di `/` |
| 2 | **Store** | 🔜 Belum dikerjakan | E-commerce, dikerjakan sesi terpisah |
| 3 | **App / Admin** | ✅ Sudah ada | Aplikasi internal, akses butuh login |

---

## Struktur Landing Page

### File-file yang terlibat

```
resources/views/
├── layouts/
│   └── landing.blade.php       ← HTML shell + SEO (EDIT DI SINI untuk ubah SEO)
└── landing/
    ├── index.blade.php          ← Assembler: @extends layout + @include 3 partial
    └── partials/
        ├── _header.blade.php    ← Navbar + Hero section
        ├── _content.blade.php   ← Semua section tengah (fitur, alur, HST, keuangan, dll)
        └── _footer.blade.php    ← Footer

public/
└── css/
    └── landing.css              ← Semua CSS landing (BUKAN Tailwind, custom CSS murni)

app/Http/Controllers/
└── LandingController.php        ← method index(), redirect ke dashboard jika sudah login
```

### Alur render

```
Browser → routes/web.php → LandingController@index
       → view('landing.index')
       → @extends('layouts.landing')
       → @include(_header) + @include(_content) + @include(_footer)
```

### Logika auth di landing

```php
// LandingController.php
public function index() {
    if (auth()->check()) {
        return redirect()->route('dashboard'); // user login → ke dashboard
    }
    return view('landing.index'); // guest → tampil landing
}
```

---

## SEO Landing Page

**Edit langsung di:**
```
resources/views/layouts/landing.blade.php  (baris 7–32)
```

Tag yang tersedia:
- `<title>` — judul tab & Google
- `<meta name="description">` — deskripsi Google
- `<meta name="keywords">` — kata kunci
- `<meta name="author">` — author (saat ini: "Taniaja")
- `<meta property="og:title/description/image">` — Open Graph (Facebook, WhatsApp)
- `<meta name="twitter:title/description/image">` — Twitter Card

> **Tidak ada** `@yield` / `@section` SEO lagi — SEO hardcode di layout langsung.
> `index.blade.php` hanya berisi `@section('content')` dengan 3 `@include`.

---

## CSS Landing Page

| Item | Keterangan |
|------|------------|
| File | `public/css/landing.css` |
| Dimuat via | `<link rel="stylesheet" href="{{ asset('css/landing.css') }}">` di layout |
| Pendekatan | Custom CSS murni (bukan Tailwind, bukan Bootstrap) |
| Font | Inter — dimuat via `@import` Google Fonts di baris pertama CSS |
| Tidak terhubung | Dengan `resources/css/app.css` atau Vite pipeline |

> Jika ingin edit tampilan landing, **edit `public/css/landing.css`** — bukan `app.css`.

---

## Routes (web.php)

File: `routes/web.php` — dibagi 4 section dengan komentar blok:

```
[1] LANDING PAGE   → Route::get('/')        → LandingController@index
[2] AUTH           → /login, /register, /forgot-password, /reset-password
[3] STORE          → TODO (belum ada route)
[4] APP / ADMIN    → middleware('auth') → /dashboard, /greenhouses, /seasons, dst.
```

> **Penting:** Semua route App/Admin diprefix nama `web.*` agar tidak bentrok
> dengan `Route::apiResource` di `routes/api.php`.

---

## Section Landing Page (urutan dari atas)

| # | Section | Partial | ID anchor |
|---|---------|---------|-----------|
| 1 | Navbar | `_header.blade.php` | — |
| 2 | Hero | `_header.blade.php` | — |
| 3 | Fitur | `_content.blade.php` | `#fitur` |
| 4 | Alur Kerja | `_content.blade.php` | `#alur` |
| 5 | Jadwal HST | `_content.blade.php` | `#hst` |
| 6 | Keuangan | `_content.blade.php` | `#keuangan` |
| 7 | Contoh Penggunaan | `_content.blade.php` | — |
| 8 | Roadmap | `_content.blade.php` | `#roadmap` |
| 9 | CTA | `_content.blade.php` | `#mulai` |
| 10 | Footer | `_footer.blade.php` | — |

---

## CSS Variables Utama (landing.css)

```css
--bg: #0b120e          /* background utama (hijau sangat gelap) */
--grass: #78a85a       /* hijau primer (tombol, aksen) */
--grass2: #a5c76e      /* hijau sekunder (hover, highlight, eyebrow) */
--moss: #416d42        /* hijau tua (logo gradient) */
--soil: #6b4931        /* coklat tanah (nomor step) */
--cream: #f2ead8       /* warna teks utama */
--muted: #a9b4a8       /* teks sekunder/redup */
--line: rgba(211,226,202,.12)  /* border halus */
```

---

## Hal yang Belum Dikerjakan / TODO

- [ ] **Responsive** — perlu perbaikan di mobile (dilaporkan user ada isu)
- [ ] **Store** — struktur route, layout, controller belum dibuat
- [ ] **OG Image** — file `public/images/og-landing.png` belum ada
- [ ] **Favicon** — `public/favicon.ico` perlu dicek/diganti
- [ ] **Font lokal** — Inter saat ini dari Google Fonts CDN, belum self-hosted penuh

---

## Perintah Berguna

```bash
# Jalankan dev server
php artisan serve

# Cek semua route yang terdaftar
php artisan route:list

# Cek route landing saja
php artisan route:list --name=landing

# Clear view cache jika ada perubahan blade tidak tampil
php artisan view:clear
```
