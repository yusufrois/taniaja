# Phase 9 (bagian 7) — Sidebar Collapsible + Topbar Lengkap

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php
```
Cuma 1 file — semua perubahan (sidebar, topbar, mobile drawer) ada di
layout ini. Tidak ada perubahan CSS/PHP lain, tidak perlu `composer`/`npm install`
apapun (Alpine.js sudah otomatis ikut Livewire 3, tidak perlu ditambah).

## Setelah menyalin
```bash
npm run build
```
Hard refresh browser (Ctrl+Shift+R). Coba juga resize jendela browser
jadi sempit (atau buka lewat HP) untuk lihat versi mobile-nya.

## Yang ditambahkan sesuai request Anda

1. **Sidebar kiri, bisa dibuka/tutup** — klik tombol "‹ Tutup Menu" di
   bawah sidebar untuk mengecilkan jadi cuma ikon (lebih hemat tempat),
   klik lagi untuk buka penuh.
2. **Logo bisa diklik** — di sidebar maupun topbar (versi mobile),
   klik logo akan selalu kembali ke `/dashboard`.
3. **Jam & tanggal real-time** — muncul di topbar (update tiap 30 detik),
   format Indonesia (mis. "Sen, 24 Agu, 14:30").
4. **Ikon Pengaturan (⚙️)** — sudah ada tombolnya di topbar, tapi belum
   ada halaman di baliknya (placeholder, sama seperti menu Greenhouse
   dkk yang belum dibuat).
5. **Info akun jadi lebih hidup** — saya tambahkan avatar bulat berisi
   inisial nama (mis. "C" untuk "Company Owner Demo") di sebelah nama,
   bukan cuma teks polos.
6. **Mobile TIDAK numpuk lagi** — di layar sempit, sidebar otomatis
   hilang total, diganti tombol ☰ (hamburger) di topbar. Klik itu untuk
   buka menu sebagai panel yang slide dari kiri, dengan overlay gelap
   di belakangnya (klik area gelap itu juga menutup menu).

## Kenapa Alpine.js tidak perlu di-install terpisah

Livewire 3 (yang sudah Anda install dari Phase 9 bagian 1) **membawa
Alpine.js built-in** dan otomatis menjalankannya — jadi fitur buka/tutup
sidebar dan jam real-time di atas semuanya pakai Alpine (`x-data`,
`x-show`, dst) tanpa nambah dependency npm sama sekali. Ini juga
alasan kenapa tidak perlu `npm install` apapun untuk update ini.

## Yang masih placeholder (belum berfungsi)
- Tombol ⚙️ Pengaturan — belum ada halaman
- Menu Greenhouse, Musim Tanam, Keuangan, Laporan — belum ada halaman
  (ini yang akan kita bangun di langkah-langkah Phase 9 berikutnya)

Kalau ada lagi yang kepikiran mau ditambah/diubah, kabari saja.
