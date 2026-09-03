# Perbaikan Fase A6 — Urutan Copy & Bug Urutan Peringatan

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/UserController.php
```
Cuma 1 file — INI FILE PALING FINAL, sudah gabungan dari A5 (perbaikan
Super Admin) + perbaikan baru (urutan peringatan pakai `id`, bukan
`created_at`).

## PENTING — supaya tidak ketimpa lagi

File `UserController.php` sudah diedit berkali-kali (A3 → A4 → A5 →
sekarang A6). **Jangan copy dari paket versi lama manapun lagi** untuk
file ini — anggap paket ini (A6) sebagai versi TERAKHIR yang berlaku
untuk `UserController.php`. Kalau nanti ada perbaikan lagi, saya akan
selalu kirim versi gabungan terbaru seperti ini, bukan cuma "tambahan"
yang mengasumsikan Anda tahu urutan tumpuk-menumpuknya.

## Setelah menyalin
```bash
php artisan route:clear
php artisan test
```
Semua 96 test seharusnya PASS sekarang.

## Ringkasan 2 masalah yang diperbaiki

1. **Super Admin masih 404** — file `UserController.php` yang aktif di
   project Anda kemungkinan besar ke-timpa balik ke versi lama (dari
   paket A4) karena urutan copy-paste. Sudah dipastikan versi di paket
   ini benar (ada `assertSameCompanyOrSuperAdmin` + `isSuperAdmin`).
2. **Urutan peringatan terbalik saat dibuat cepat berurutan** — 2
   peringatan yang dibuat dalam detik yang sama bisa punya `created_at`
   identik, jadi "urutkan berdasarkan tanggal" tidak bisa dipastikan
   urutannya. Diganti urutkan berdasarkan `id` (selalu naik, tidak
   pernah kembar), jadi "terbaru dulu" selalu benar.
