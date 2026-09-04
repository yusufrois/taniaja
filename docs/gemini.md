# GEMINI.md — Index Konteks Proyek Taniaja (AgroFlow)

> File ini dibuat untuk mempercepat sesi pengembangan selanjutnya.
> Baca file ini di awal sesi agar tidak perlu menjelaskan ulang struktur yang sudah ada.
> **Last updated:** 2026-09-04

---

## Identitas Proyek

| Item | Nilai |
|------|-------|
| Nama App | AgroFlow |
| Brand | LadangWohIjo |
| Framework | Laravel 12 |
| Build Tool | Vite (bawaan Laravel) |
| CSS App/Admin | Tailwind CSS v4 via `@tailwindcss/vite` |
| CSS Landing | Custom CSS murni di `public/css/landing.css` |
| Auth | Livewire + session (bukan Sanctum untuk web UI) |
| URL Lokal | `http://taniaja.test/` atau `http://127.0.0.1:8000/` |

---

## Tiga Konsep Utama (Scope)

| # | Konsep | Status | Keterangan |
|---|--------|--------|------------|
| 1 | **Landing Page** | ✅ Selesai | `/`, `/tentang`, `/blog`, `/kontak` |
| 2 | **Store** | 🔜 Belum dikerjakan | Route placeholder di web.php section [3] |
| 3 | **App / Admin** | ✅ Sudah ada | Aplikasi internal, akses butuh login |

---

## Struktur Landing Page — Lengkap

```
resources/views/
├── layouts/
│   └── landing.blade.php        ← HTML shell + SEO hardcode (EDIT DI SINI untuk ubah SEO)
└── landing/
    ├── index.blade.php           ← Assembler halaman utama (/)
    ├── tentang.blade.php         ← Halaman /tentang
    ├── blog.blade.php            ← Halaman /blog
    ├── kontak.blade.php          ← Halaman /kontak
    └── partials/
        ├── _navbar.blade.php     ← Navbar BERSAMA (dipakai semua halaman landing)
        ├── _header.blade.php     ← @include(_navbar) + Hero section (hanya untuk /)
        ├── _content.blade.php    ← Semua section tengah (hanya untuk /)
        └── _footer.blade.php     ← Footer (dipakai semua halaman)

public/
└── css/
    └── landing.css               ← Semua CSS landing page

app/Http/Controllers/
└── LandingController.php         ← index, tentang, blog, kontak methods
```

---

## Alur Render per Halaman

```
/           → LandingController@index   → landing/index.blade.php
                                            @include(_navbar) via _header
                                            @include(_content)
                                            @include(_footer)

/tentang    → LandingController@tentang → landing/tentang.blade.php
                                            @include(_navbar)
                                            [konten tentang]
                                            @include(_footer)

/blog       → LandingController@blog    → landing/blog.blade.php
                                            @include(_navbar)
                                            [konten blog]
                                            @include(_footer)

/kontak     → LandingController@kontak  → landing/kontak.blade.php
                                            @include(_navbar)
                                            [form kontak]
                                            @include(_footer)
```

---

## Logika Auth di Landing

```php
// LandingController.php — method index()
if (auth()->check()) {
    return redirect()->route('dashboard'); // user sudah login → dashboard
}
return view('landing.index'); // guest → tampil landing
```

Halaman `/tentang`, `/blog`, `/kontak` tidak ada pengecekan auth — bisa diakses siapa saja.

---

## SEO Landing Page

**Edit langsung di:**
```
resources/views/layouts/landing.blade.php  (baris 7–32)
```

Tag yang ada (semua hardcode, tidak ada @yield):
- `<title>` — judul tab & Google
- `<meta name="description">` — deskripsi Google
- `<meta name="keywords">` — kata kunci (lihat versi lengkap di file)
- `<meta name="author">` — "Taniaja"
- `<meta name="robots">` — "index, follow"
- `<meta property="og:*">` — Open Graph (Facebook, WhatsApp, LinkedIn)
- `<meta name="twitter:*">` — Twitter Card
- `<link rel="canonical">` — URL canonical

> **Catatan:** `index.blade.php` dan halaman lain TIDAK memiliki @section SEO.
> SEO dikelola terpusat di `layouts/landing.blade.php`.

---

## Routes (web.php) — Section Lengkap

```
[1] LANDING PAGE     (tanpa auth)
    GET /            → LandingController@index    → 'landing'
    GET /tentang     → LandingController@tentang  → 'landing.tentang'
    GET /blog        → LandingController@blog     → 'landing.blog'
    GET /kontak      → LandingController@kontak   → 'landing.kontak'

[2] AUTH             (middleware: guest)
    GET /login
    GET /register
    GET /forgot-password
    GET /reset-password/{token}

[3] STORE            → TODO (belum ada route)

[4] APP / ADMIN      (middleware: auth)
    GET /dashboard
    GET /greenhouses, /crops, /varieties, /suppliers, /customers
    GET /grades, /expense-categories, /asset-categories
    GET /seasons, /seasons/{season}
    GET /expenses, /chart-of-accounts, /capital, /debts, /assets
    GET /reports, /settings/modules
    POST /logout
```

> Semua route App/Admin diprefix nama `web.*` untuk hindari konflik dengan
> `Route::apiResource` di `routes/api.php`.

---

## Navbar (_navbar.blade.php)

Navbar bersama dipakai di **semua** halaman landing:

| Link | Route | Keterangan |
|------|-------|------------|
| Logo | `route('landing')` | Klik logo → ke `/` |
| Beranda | `route('landing')` | Active jika di `/` |
| Fitur | `route('landing').'#fitur'` | Scroll ke section fitur |
| Tentang | `route('landing.tentang')` | Active jika di `/tentang` |
| Blog | `route('landing.blog')` | Active jika di `/blog` |
| Kontak | `route('landing.kontak')` | Active jika di `/kontak` |
| CTA Button | `route('login')` | "Masuk ke Sistem →" |

Active state menggunakan Blade `@class(['active' => request()->routeIs('...')])`.

---

## CSS Landing Page (landing.css)

| Item | Keterangan |
|------|------------|
| File | `public/css/landing.css` |
| Dimuat | `<link rel="stylesheet">` di `layouts/landing.blade.php` |
| Font | Inter via `@import` Google Fonts (baris pertama CSS) |
| Pendekatan | Custom CSS murni — **bukan** Tailwind, **bukan** Bootstrap |
| Tidak terhubung | Dengan `resources/css/app.css` atau Vite pipeline |

**Sections di CSS:**
1. Google Fonts import + `:root` CSS variables
2. Reset & base styles
3. Navigation
4. Buttons
5. Hero
6. Mockup Dashboard UI
7. Sections (fitur, alur, HST, keuangan, roadmap, CTA)
8. Footer
9. Responsive (900px, 600px)
10. **Halaman Tambahan** — Page Hero, About, Blog, Contact (ditambah di sesi ini)

---

## CSS Variables Utama

```css
--bg: #0b120e          /* background utama (hijau sangat gelap) */
--grass: #78a85a       /* hijau primer (tombol, aksen) */
--grass2: #a5c76e      /* hijau sekunder (hover, highlight, eyebrow) */
--moss: #416d42        /* hijau tua (logo gradient) */
--soil: #6b4931        /* coklat tanah (nomor step) */
--soil2: #8b6140       /* coklat muda */
--cream: #f2ead8       /* warna teks utama */
--muted: #a9b4a8       /* teks sekunder/redup */
--line: rgba(211,226,202,.12)  /* border halus */
--shadow: 0 22px 70px rgba(0,0,0,.35)
```

---

## Section Landing Page `/` (urutan dari atas)

| # | Section | Partial | ID Anchor |
|---|---------|---------|-----------|
| 1 | Navbar | `_navbar.blade.php` | — |
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

## Halaman Tambahan

### `/tentang` — Tentang Kami
- Page Hero
- Cerita origin LadangWohIjo (about-lead grid)
- Stats Row (3+ tahun, 9 modul, 100% dari lapangan)
- Visi & Misi (vision-grid 2 kolom)
- Nilai-Nilai (value-grid 3 kolom, 6 item)
- CTA → `/login`

### `/blog` — Blog & Artikel
- Page Hero
- Blog Grid (3 kolom, 6 artikel placeholder)
- Setiap artikel: badge "Segera Hadir", thumbnail emoji, tag kategori
- Newsletter CTA → `/kontak`

### `/kontak` — Kontak
- Page Hero
- Contact Grid (form kiri, info kontak kanan)
- Form: Nama, Email, Subjek (dropdown), Pesan — action="#" (belum ada backend)
- Info: WhatsApp, Email, Lokasi, Jam Respons
- FAQ singkat inline

---

## Hal yang Belum Dikerjakan / TODO

- [ ] **Responsive mobile** — user melaporkan ada isu, belum diperbaiki
- [ ] **Form kontak backend** — form `/kontak` action="#", belum ada email handler
- [ ] **Store** — struktur route, layout, controller belum dibuat
- [ ] **OG Image** — `public/images/og-landing.png` belum ada
- [ ] **Favicon** — `public/favicon.ico` perlu dicek/diganti
- [ ] **sitemap.xml** — belum dibuat (rekomendasi: `spatie/laravel-sitemap`)
- [ ] **JSON-LD Schema** — belum ada structured data Organization/WebSite
- [ ] **Font lokal** — Inter masih dari Google Fonts CDN, belum self-hosted

---

## Perintah Berguna

```bash
# Jalankan dev server
php artisan serve

# Cek semua route landing
php artisan route:list --path=/tentang
php artisan route:list --name=landing

# Clear view cache jika perubahan blade tidak tampil
php artisan view:clear

# Clear semua cache
php artisan optimize:clear
```

---

## File Docs Terkait

| File | Isi |
|------|-----|
| `docs/gemini.md` | File ini — index konteks proyek |
| `docs/index.html` | Desain HTML statis original (referensi) |
| `docs/prompt-landing-page-laravel12.md` | Prompt awal untuk sesi landing page |
