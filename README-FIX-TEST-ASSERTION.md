# Perbaikan Test: Ekspektasi Status Code yang Salah

## File yang MENIMPA file lama
```
tests/Feature/Attendance/AttendanceTest.php
```

## Kenapa
Bug cast tanggal kemarin sudah benar-benar beres — `updateOrCreate()`
sekarang berhasil menemukan record yang sudah ada. Tapi test saya
salah menebak: panggilan KEDUA (cek-in ulang di hari yang sama) itu
secara semantik adalah **UPDATE** (200 OK), bukan **CREATE baru**
(201 Created) — jadi wajar Laravel mengembalikan 200, bukan 201.
Test-nya yang saya perbaiki, bukan kode aplikasinya.

## Setelah menyalin
```bash
php artisan test
```
