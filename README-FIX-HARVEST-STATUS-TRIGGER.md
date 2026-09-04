# Perbaikan: Status Musim Tanam Tidak Berubah Setelah Panen

## Penyebab

Kalau Musim Tanam itu **sudah pernah** punya field "Tanggal Panen
Aktual" terisi sebelumnya (misal dari percobaan lewat form edit
Musim Tanam), kode saya SALAH memakai tanggal LAMA itu untuk
menentukan apakah status boleh berubah — bukan tanggal Panen yang
BARU SAJA Anda catat. Kalau tanggal lama itu di masa depan atau
sudah tidak relevan, status jadi tidak pernah berubah.

## Solusi

Sekarang pengecekan SELALU pakai tanggal dari Panen yang baru
dicatat (bukan field lama di Musim Tanam) — jadi status pasti
berubah begitu Panen dicatat, terlepas dari histori field itu
sebelumnya.

## File yang MENIMPA file lama
```
app/Livewire/Harvest/Manage.php
```
File baru: 1 test tambahan (di file test yang sama).

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=HarvestSeasonStatusTest
php artisan test
```

Coba lagi catat Panen untuk Musim Tanam yang tadi tetap "Aktif" —
seharusnya sekarang berubah jadi "Panen".
