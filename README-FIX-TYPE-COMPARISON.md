# Perbaikan: Perbandingan Tipe Data di Test

## File yang MENIMPA file lama
```
tests/Feature/Reports/TraceabilityReportsTest.php
```

## Penyebab
Bukan bug di kode aplikasi — 4 dari 5 test yang berkaitan dengan
angka yang sama (total_harvested, total_sold, total_remaining_stock)
sudah PASS, membuktikan datanya benar. Yang gagal cuma perbandingan
`===` (strict) antara `30.0` dan hasil decode JSON `30` (integer) —
PHP menulis angka desimal tanpa pecahan sebagai teks JSON tanpa
".0" di belakang, jadi saat dibaca ulang jadi integer. Diperbaiki
dengan `(float)` cast eksplisit sebelum dibandingkan.

## Setelah menyalin
```bash
php artisan test --filter=TraceabilityReportsTest
```
Semua 5 test seharusnya PASS.
