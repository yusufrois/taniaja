# Phase 1 — Setup Notes

## Cara pakai file-file ini
Salin folder ini ke atas project Laravel baru Anda, misalnya:
```bash
composer create-project laravel/laravel taniaja
```
lalu timpa file yang namanya sama dengan isi paket ini.

## Dependency yang perlu di-install
Untuk Laravel 11/12, jalankan ini di dalam folder project (`cd taniaja`) — perintah
ini sekaligus membuat `routes/api.php` dan meng-install Sanctum dalam satu langkah:
```bash
php artisan install:api
```
Kalau ditanya "The routes/api.php file already exists. Do you want to replace it?"
setelah Anda menyalin file paket ini, jawab **No** — supaya `routes/api.php` versi
Phase 1 (sudah berisi endpoint login/register) tidak tertimpa balik ke versi kosong.

Untuk Laravel 10 ke bawah, gunakan cara manual:
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

## Konfigurasi
1. Di `config/auth.php` / `app/Http/Kernel.php` (Laravel 10) atau `bootstrap/app.php`
   (Laravel 11+), pastikan `EnsureFrontendRequestsAreStateful` + `auth:sanctum`
   aktif untuk grup `api`.
2. Tambahkan `Laravel\Sanctum\HasApiTokens` sudah ada di `app/Models/User.php`
   (sudah termasuk di file ini).
3. Jalankan migration lalu seeder:
```bash
php artisan migrate
php artisan db:seed
```

## Login demo (setelah seeding)
```
owner@ladangwohijo.test     / password
manager@ladangwohijo.test   / password
supervisor@ladangwohijo.test/ password
worker@ladangwohijo.test    / password
finance@ladangwohijo.test   / password
```
Semua terdaftar di company `LadangWohIjo` (code: LWI), dengan GH-A dan GH-B.

## Jalankan test tenant isolation (WAJIB hijau sebelum Phase 2)
```bash
php artisan test --filter=TenantIsolationTest
```
Test ini yang memverifikasi Catatan Arsitektur #1 (global scope company_id)
benar-benar berfungsi: user company A tidak bisa melihat data company B,
company_id otomatis terisi saat create, dan Super Admin bisa lihat semua.

## Yang BELUM ada di Phase 1 (menyusul Phase 2+)
- Policy per model (baru relevan setelah ada resource selain Greenhouse)
- Crop, Variety, Supplier, Customer, Grade, Expense Category (Phase 2)
- Asset model (dirujuk di `Greenhouse::constructionCost()` — akan dibuat Phase 5,
  method tsb baru bisa dipanggil setelah tabel `assets` ada)
- Middleware/Policy untuk permission matrix di Bagian E dokumen arsitektur —
  sengaja belum dibuat granular karena modul yang diproteksi belum ada.

## Struktur file di paket ini
```
database/migrations/2026_08_24_000001_create_companies_table.php
database/migrations/2026_08_24_000002_add_company_id_to_users_table.php
database/migrations/2026_08_24_000003_create_roles_and_permissions_tables.php
database/migrations/2026_08_24_000004_create_audit_logs_table.php
database/seeders/RolePermissionSeeder.php
database/seeders/DemoCompanySeeder.php
database/seeders/DatabaseSeeder.php
app/Models/Concerns/BelongsToCompany.php
app/Models/Company.php
app/Models/User.php
app/Models/Role.php
app/Models/Permission.php
app/Models/AuditLog.php
app/Models/Greenhouse.php   (contoh pola untuk Phase 2 dst)
app/Http/Controllers/Api/V1/AuthController.php
routes/api.php
tests/Feature/Tenancy/TenantIsolationTest.php
```
