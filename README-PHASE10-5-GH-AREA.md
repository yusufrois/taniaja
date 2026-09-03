# Phase 10 (Bagian 4): Bug #5 — Luas Greenhouse Otomatis

## Solusi

Luas (`area`) sekarang **selalu dihitung otomatis** dari Panjang ×
Lebar — di level MODEL (`Greenhouse::booted()`), bukan di form saja.
Ini berarti berlaku untuk **API, web, ATAU cara input apapun ke
depannya (termasuk Flutter nanti)** — sesuai prinsip Phase 10, satu
sumber kebenaran.

- Kirim nilai `area` manual? **Diabaikan**, tetap dihitung ulang dari
  Panjang × Lebar
- Isi cuma salah satu (Panjang saja / Lebar saja)? Luas yang lama
  **tidak diubah** (tidak tiba-tiba jadi kosong)
- Form web sekarang menampilkan Luas sebagai **pratinjau otomatis**
  (bukan kotak isian) — langsung update begitu Anda mengetik Panjang
  atau Lebar

## File yang MENIMPA file lama
```
app/Models/Greenhouse.php
app/Http/Requests/StoreGreenhouseRequest.php
app/Http/Requests/UpdateGreenhouseRequest.php
app/Livewire/Greenhouse/Manage.php
resources/views/livewire/greenhouse/manage.blade.php
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=GreenhouseAutoAreaTest
php artisan test --filter=GreenhouseManageTest
php artisan test --filter=GreenhouseCrudTest
```

## Lanjut

Tinggal **#9 (proteksi hapus kategori/akun yang masih dipakai
transaksi lain)** — bagian terakhir dari Prioritas 2.
