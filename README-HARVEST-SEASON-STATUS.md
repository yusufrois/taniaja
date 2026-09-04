# Perbaikan: Panen Sekarang Otomatis Ubah Status Musim Tanam

## Yang diperbaiki

Sesuai diskusi kita — catat Panen sekarang **otomatis** mengubah
status Musim Tanam jadi **"Panen"** (kalau belum), TAPI **TIDAK
PERNAH** otomatis jadi "Selesai" — itu tetap harus ditandai manual,
sesuai desain yang sudah ada.

Untuk kasus panen berkali-kali (cabe ronde 1, 2, 3, dst): field
"Tanggal Panen Aktual" di Musim Tanam cuma terisi dari **panen
pertama** — ronde-ronde berikutnya tidak menggeser tanggal itu lagi.

Kalau Musim Tanam sudah "Selesai" duluan, catat Panen susulan **tidak
akan** menariknya balik jadi "Panen" — status Selesai itu final.

## Cara kerja teknis

Pakai `SeasonStatusService` yang **sudah ada** (bukan logic baru
terpisah) — sama seperti yang selama ini dipakai saat isi "Tanggal
Panen Aktual" lewat form edit Musim Tanam. Jadi kedua jalur (form
edit manual & catat Panen) tetap konsisten satu sama lain.

## File yang MENIMPA file lama
```
app/Livewire/Harvest/Manage.php
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=HarvestSeasonStatusTest
php artisan test
```
