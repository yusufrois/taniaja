# RBAC Fase A5 — Super Admin Bisa Kelola/Suspend Owner (Setup Notes)

Menjawab: "saya juga pemilik sistem, jadi harus bisa suspend akun
owner kalau melanggar".

## 2 Bug yang ditemukan dan diperbaiki sekaligus

1. **Super Admin sebenarnya TIDAK BISA kelola user manapun** —
   `UserController` (dari Fase A3/A4) selalu bandingkan
   `$target->company_id !== auth()->user()->company_id`. Untuk Super
   Admin, `company_id` miliknya sendiri itu `NULL` — jadi perbandingan
   itu SELALU benar (dianggap beda company), Super Admin selalu kena
   404 di endpoint manapun yang menyentuh user tertentu. Padahal Super
   Admin didesain untuk bisa akses lintas company.
2. **Tidak ada akun Super Admin yang beneran bisa dipakai login** —
   *role* `super-admin` sudah ada sejak Phase 1, tapi tidak pernah ada
   *user* yang di-attach ke situ lewat seeder manapun.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/UserController.php   (tambah helper
                                                    assertSameCompanyOrSuperAdmin,
                                                    index() bisa lintas company)
database/seeders/DatabaseSeeder.php               (tambah SuperAdminSeeder)
```
File baru: `SuperAdminSeeder.php` + test-nya.

## Setelah menyalin
```bash
php artisan db:seed
php artisan test --filter=SuperAdminUserManagementTest
```

## Akun Super Admin demo

```
Email: superadmin@taniaja.test
Password: password
```
**Ganti password ini kalau sudah masuk tahap produksi sungguhan** —
ini cuma untuk development.

## Cara pakai

Login sebagai Super Admin (lewat `/api/v1/login` biasa — sama seperti
user lain), lalu:
```
GET   /api/v1/users                        → lihat SEMUA user LINTAS company
GET   /api/v1/users?company_id=5           → filter ke 1 company tertentu
PATCH /api/v1/users/{id}/suspend           → suspend siapapun, termasuk Owner
POST  /api/v1/users/{id}/warnings          → kasih peringatan ke siapapun
```
Owner biasa (bukan Super Admin) TETAP hanya bisa lihat/kelola staff di
company-nya sendiri — sudah dites ulang supaya perbaikan ini tidak
melonggarkan batasan Fase A3 yang lama.

## Yang SENGAJA belum dibuat

- **Suspend seluruh company** (bukan cuma 1 akun Owner) — kalau
  pelanggarannya di level perusahaan (bukan cuma 1 orang), mungkin
  Anda mau nonaktifkan SELURUH company sekaligus (semua user-nya tidak
  bisa login). Ini scope terpisah, belum saya buat — kabari kalau
  dibutuhkan.
- **Panel admin terpisah** (`/admin/...`) — saat ini Super Admin pakai
  endpoint yang sama dengan Owner biasa (`/api/v1/users`), cuma dengan
  akses lebih luas. Kalau nanti mau UI/endpoint yang benar-benar
  terpisah untuk platform admin, itu penambahan lain.

## Lanjut ke roadmap

Fase A, A2-A5, B semuanya selesai. Berikutnya Fase C (stok input
pertanian/pupuk), sesuai roadmap awal.
