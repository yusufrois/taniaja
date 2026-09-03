# Perbaikan: read_at Tidak Muncul Setelah Mark as Read

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/NotificationController.php
```

## Yang saya lakukan
Tambah `$notification->refresh()` sebelum data dikembalikan ke
response — memaksa ambil ulang data segar dari database, bukan
mengandalkan objek yang ada di memori sesudah `update()`.

## Catatan jujur
Saya belum 100% yakin akar penyebab pastinya kenapa objek di memori
tidak langsung mencerminkan `read_at` yang baru — kemungkinan ada
sesuatu yang subtle soal state model setelah `update()` di setup
project Anda. Tapi `refresh()` ini SELALU benar terlepas dari
penyebab persisnya (memaksa baca ulang dari database itu solusi yang
pasti bekerja), jadi saya pakai ini sebagai perbaikan yang aman.

Kalau nanti ketemu bug serupa (data yang baru di-update tidak
langsung muncul di response) di controller lain, pola `->refresh()`
ini bisa jadi solusi cepat yang sama.

## Setelah menyalin
```bash
php artisan test --filter=TaskTest
```
Semua 9 test seharusnya PASS sekarang.
