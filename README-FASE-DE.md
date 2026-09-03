# Roadmap Tambahan Fase D & E — Surat Jalan + Nota (dengan PDF)

## PENTING — WAJIB INSTALL PACKAGE PDF DULU

Sebelum menyalin file ini, jalankan di terminal:
```bash
composer require barryvdh/laravel-dompdf
```
Tanpa ini, endpoint PDF (`.../pdf`) akan error 500 — tapi endpoint
lain (buat/lihat/hapus Surat Jalan) tetap normal.

## File yang MENIMPA file lama
```
app/Models/Purchase.php                              (basis dari Fase
                                                        J/K, sudah ada
                                                        creator())
app/Http/Controllers/Api/V1/PurchaseController.php   (+ method pdf())
routes/api.php                                        (+ route baru)
database/seeders/RolePermissionSeeder.php            (+ modul
                                                        delivery_note)
database/seeders/RoleCapabilitySeeder.php            (assign sesuai
                                                        skema)
```
File baru: 2 migration, 2 model, 1 policy, 2 service, 1 request,
2 resource, 1 controller, 2 view PDF, 1 test.

## Setelah menyalin
```bash
composer require barryvdh/laravel-dompdf
php artisan migrate
php artisan db:seed
php artisan test --filter=DeliveryNoteTest
```

**Catatan**: test PDF sengaja TIDAK saya buat otomatis — saya tidak
bisa memastikan package `barryvdh/laravel-dompdf` benar-benar
ter-install & berfungsi di lingkungan pengujian tanpa menjalankannya
sendiri. Coba manual lewat browser/Postman:
```
GET /api/v1/purchases/{id}/pdf
GET /api/v1/delivery-notes/{id}/pdf
```

## Endpoint baru

**Fase D — Surat Jalan:**
```
POST   /delivery-notes              — buat surat jalan (+item barang)
GET    /delivery-notes              — daftar
GET    /delivery-notes/{id}         — detail
GET    /delivery-notes/{id}/pdf     — versi PDF, bisa di-share WA
DELETE /delivery-notes/{id}
```
`sale_id` opsional — kalau diisi, status lunas/belum **otomatis**
ikut dari Invoice terkait (`invoice_payment_status` di respons),
sesuai yang Anda tanyakan soal alur Surat Jalan → Invoice → lunas.

**Fase E — Nota Panen/Pembayaran ke Petani:**
```
GET /purchases/{id}/pdf             — nota PDF dari data Purchase
                                       yang sudah ada sejak Phase 6
```
Bisa langsung di-share dari lokasi (di tempat petani) tanpa perlu
balik kantor dulu, sesuai yang Anda minta.

## Nomor Surat Jalan
Format `SJ/{kode_perusahaan}/{YYYYMM}/{urutan}` — persis pola yang
sama dengan nomor Invoice (`INV/...`) sejak Phase 7, cuma prefix beda.

## Cara pakai PDF di aplikasi (untuk nanti, Phase 9 UI)
Endpoint `.../pdf` mengembalikan file PDF langsung (bukan JSON).
Untuk fitur "share ke WA": aplikasi tinggal unduh file PDF ini, lalu
serahkan ke fitur share bawaan HP (Android/iOS) — ini standar, tidak
perlu integrasi WhatsApp API khusus.

## Yang SENGAJA belum dibuat
- **PDF Invoice** dan **PDF Slip Gaji** (yang ditunda dari Fase H) —
  infrastrukturnya (package, pola Blade view) sudah siap dipakai,
  tinggal saya tambahkan viewnya kapan saja kalau diminta — sengaja
  tidak sekalian dibuat sekarang supaya paket ini tidak melebar.
- **Update DeliveryNote** (cuma ada create+delete) — untuk koreksi
  besar, hapus lalu buat ulang untuk saat ini.

## Lanjut ke roadmap
Fase D, E selesai. Tersisa: Fase L (Akuntansi Double-Entry, paling
besar) dan Fase F (rangkai role siap pakai, paling akhir).
