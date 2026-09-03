# Phase 9 (bagian 1) — Login & Register UI (Setup Notes)

Ini adalah frontend PERTAMA di project ini — sebelumnya semua API-only.
Setup-nya beda dari phase-phase sebelumnya (butuh Composer package baru
+ npm), jadi ikuti urutan di bawah PERSIS.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/AuthController.php   (di-refactor pakai
                                                    CompanyRegistrationService,
                                                    PERILAKU API TIDAK BERUBAH)
routes/web.php                                    (diganti total —
                                                    halaman "Laravel welcome"
                                                    bawaan akan HILANG,
                                                    diganti redirect ke /login)
```
Semua file lain BARU.

## Langkah instalasi (urutan penting)

### 1. Install Livewire
```bash
composer require livewire/livewire
```

### 2. Install Tailwind CSS
```bash
npm install -D tailwindcss postcss autoprefixer
```
Setelah itu, salin `tailwind.config.js` dan `postcss.config.js` dari paket
ini ke root project `taniaja` (timpa kalau sudah ada dari `npx tailwindcss init`).

### 3. Salin semua file paket ini
Seperti biasa — extract, copy-paste ke `C:\laragon\www\taniaja`, pilih
Replace kalau ditanya.

### 4. Cek `vite.config.js`
Buka `vite.config.js` di root project, pastikan bagian `input` di dalam
plugin `laravel(...)` menyertakan `resources/css/app.css` DAN
`resources/js/app.js`, contoh:
```js
laravel({
    input: ['resources/css/app.css', 'resources/js/app.js'],
    refresh: true,
}),
```
Biasanya sudah begini secara default dari `laravel new` — kalau sudah
sama persis, tidak perlu diubah.

### 5. Build asset frontend
```bash
npm run build
```
(atau `npm run dev` kalau mau mode watch selagi development — biarkan
terminal itu tetap terbuka)

### 6. Jalankan server dan coba
```bash
php artisan serve
```
Buka browser ke `http://127.0.0.1:8000` — harus otomatis redirect ke
`/login`. Coba:
- **Login** pakai `owner@ladangwohijo.test` / `password` (data demo dari
  Phase 1) → harus masuk ke halaman dashboard placeholder
- **Register** perusahaan baru lewat `/register` → otomatis login habis
  daftar

### 7. Jalankan test otomatis
```bash
php artisan test --filter=WebAuthTest
```
Test ini mencakup: halaman login termuat, login berhasil dengan kredensial
benar, login gagal dengan password salah, user nonaktif tidak bisa login,
register perusahaan baru berhasil + otomatis punya role Owner, kode
perusahaan duplikat ditolak, dan guest tidak bisa akses `/dashboard`.

## Kenapa desainnya begini

1. **Web login (session) TERPISAH TOTAL dari API login (token Sanctum).**
   Keduanya membaca tabel `users` yang sama, tapi mekanismenya beda:
   - Postman/Flutter nanti → tetap pakai `/api/v1/login`, dapat token
   - Browser (halaman ini) → pakai session Laravel biasa, tidak ada token
   Anda TIDAK PERLU login API terpisah untuk pakai halaman web ini, dan
   sebaliknya — keduanya independen sepenuhnya.
2. **`CompanyRegistrationService`** — logic bikin company+owner baru
   sekarang di 1 tempat, dipakai API (`AuthController`) dan Web
   (`RegisterCompany` Livewire) sekaligus. Kalau nanti aturan pendaftaran
   berubah (mis. tambah field wajib), cukup ubah di 1 file.
3. **Dashboard masih placeholder** — sengaja kosong dulu, biar alur
   login→dashboard bisa langsung dicoba SEKARANG tanpa nunggu seluruh
   dashboard asli (chart, ringkasan) selesai dibangun di langkah
   berikutnya.
4. **Livewire dipakai untuk form**, bukan Blade+Controller klasik —
   sesuai prioritas stack di Section 2 dokumen arsitektur awal, dan jadi
   pola yang akan dipakai berulang di halaman-halaman berikutnya.

## Kalau ada error umum

- **"Class Livewire\Component not found"** → `composer require
  livewire/livewire` belum jalan atau gagal, cek koneksi internet Composer.
- **Halaman tampil tapi tanpa styling sama sekali (putih polos, tidak ada
  warna hijau/rounded)** → `npm run build` belum dijalankan, atau
  `tailwind.config.js` belum ada di root project.
- **419 Page Expired saat submit form** → biasanya cache lama, jalankan
  `php artisan optimize:clear`.

## Yang BELUM ada (menyusul langkah Phase 9 berikutnya)
- Dashboard asli dengan ringkasan + chart (Section 24)
- Sidebar navigasi lengkap (Section 31)
- Halaman CRUD untuk semua master data, budidaya, keuangan, dst
- Notifikasi in-app (Section 27)
- Form input lapangan mobile-first (Section 32)
