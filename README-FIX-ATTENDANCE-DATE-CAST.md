# Perbaikan Bug: Cast Tanggal di Attendance

## File yang MENIMPA file lama
```
app/Models/Attendance.php
```
Cuma 1 file, 1 baris yang berubah.

## Setelah menyalin
```bash
php artisan test --filter=AttendanceTest
```
Harusnya semua 12 test PASS sekarang.

## Penyebab
Cast `'date' => 'date'` (tanpa format eksplisit) di Laravel menyimpan
ke database sebagai datetime PENUH (`2026-09-01 00:00:00`), bukan
cuma tanggal. Waktu fitur "cek-in dua kali sehari" (upsert) mencari
record yang sudah ada pakai string tanggal polos (`2026-09-01`),
pencariannya tidak pernah ketemu karena beda format persis — sistem
kira belum ada record, coba `INSERT` baru, lalu nabrak batasan unik
(1 orang cuma boleh 1 absen per hari).

Diperbaiki jadi `'date' => 'date:Y-m-d'` — format disimpan dan dicari
jadi konsisten.
