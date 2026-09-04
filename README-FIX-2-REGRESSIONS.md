# Perbaikan: 2 Regresi dari Perbaikan Role Provisioning

## Regresi #1 — Seed Bagan Akun Hilang

Waktu saya perbaiki bug "peran cuma ada company owner", saya menulis
ulang `CompanyRegistrationService` dari basis file yang **ternyata
lebih lama** dari yang sudah ada di proyek Anda — basis itu belum
punya logic "seed Bagan Akun otomatis saat daftar" (dari Fase L1).
Jadi tanpa sadar, fix baru saya **menghapus** fitur lama yang sudah
benar.

**Solusi**: kedua perbaikan sekarang digabung jadi 1 — daftar
perusahaan baru otomatis dapat 16 akun Bagan Akun standar **DAN**
5 role lengkap dengan izinnya.

## Regresi #2 — Test Lama Saya Sendiri

`ModuleToggleTest` (test yang saya buat 2-3 perbaikan lalu) memberi
owner cuma izin `report.view`/`activity.view` — itu cukup SEBELUM
perbaikan "sidebar sesuai izin peran". Setelah perbaikan itu, link
Greenhouse/Musim Tanam butuh izin `greenhouse.view`/`season.view`
juga, bukan cuma modul aktif. Test lama saya belum diperbarui untuk
itu.

## File yang MENIMPA file lama
```
app/Services/Auth/CompanyRegistrationService.php
tests/Feature/Settings/ModuleToggleTest.php
```

## Setelah menyalin
```bash
php artisan test --filter=AccountingTest
php artisan test --filter=ModuleToggleTest
php artisan test
```
