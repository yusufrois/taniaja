# Phase 2 — Master Data (Setup Notes)

## Cara pakai
Timpa/tambahkan file-file ini ke project `taniaja` yang sudah berjalan dari Phase 1.
Semua file baru — TIDAK ADA yang menghapus data Phase 1 Anda.

File yang MENIMPA file lama (harus diganti):
```
routes/api.php                                  (nambah 7 endpoint master data)
database/seeders/RolePermissionSeeder.php       (nambah modul grade & expense_category)
database/seeders/DatabaseSeeder.php             (nambah RoleCapabilitySeeder)
```
Semua file lain adalah file BARU, tinggal disalin ke lokasi yang sama.

## Setelah menyalin semua file
```bash
php artisan migrate
php artisan db:seed
```
`db:seed` aman dijalankan ulang di database yang sudah ada — semua seeder Phase 1
& Phase 2 pakai `firstOrCreate`/`syncWithoutDetaching`, jadi tidak akan duplikat
atau error kalau datanya sudah ada sebagian.

**Penting:** setelah ini, user demo (owner/manager/dst) yang SUDAH ADA dari Phase 1
otomatis dapat permission baru dari `RoleCapabilitySeeder` karena permission
dilekatkan ke Role, bukan ke User — tidak perlu re-create user.

## Jalankan test
```bash
php artisan test --filter=GreenhouseCrudTest
```
Test ini memverifikasi: user dengan permission bisa create, user tanpa permission
kena 403, kode greenhouse tidak boleh duplikat dalam 1 company tapi BOLEH sama
antar company (bukti isolasi tenant tetap bekerja + validasi scoped per company).

## Endpoint baru (semua butuh header `Authorization: Bearer <token>` dari login)
```
GET/POST            /api/v1/greenhouses
GET/PUT/DELETE      /api/v1/greenhouses/{id}
GET/POST            /api/v1/crops
GET/PUT/DELETE      /api/v1/crops/{id}
GET/POST            /api/v1/varieties
GET/PUT/DELETE      /api/v1/varieties/{id}
GET/POST            /api/v1/suppliers
GET/PUT/DELETE      /api/v1/suppliers/{id}
GET/POST            /api/v1/customers
GET/PUT/DELETE      /api/v1/customers/{id}
GET/POST            /api/v1/grades
GET/PUT/DELETE      /api/v1/grades/{id}
GET/POST            /api/v1/expense-categories
GET/PUT/DELETE      /api/v1/expense-categories/{id}
```

## Cara test lewat Postman (contoh: buat Crop baru)
1. Login dulu (endpoint Phase 1), copy `token` dari response.
2. Request baru: POST `http://127.0.0.1:8000/api/v1/crops`
3. Tab Headers, tambah: `Authorization` = `Bearer <token_dari_login>`
4. Tab Body > raw > JSON: `{ "name": "Melon" }`
5. Send — harus 201 Created (kalau login sebagai owner/manager) atau 403
   (kalau login sebagai worker, karena worker tidak dapat permission apapun
   sesuai matrix).

## Yang berubah secara arsitektur di Phase 2
- **Role & Permission benar-benar aktif** sekarang. Di Phase 1, tabel permission
  dibuat tapi tidak pernah dilekatkan ke role manapun — jadi `hasPermission()`
  selalu `false` untuk siapapun selain Super Admin. `RoleCapabilitySeeder`
  memperbaiki ini sesuai Role & Permission Matrix (Bagian E dokumen arsitektur).
- **BaseMasterDataPolicy** (`app/Policies/Concerns/AuthorizesByModulePermission.php`)
  jadi pola dipakai ulang untuk semua policy modul berikutnya (Season, Expense,
  dst di Phase 5+) — tinggal extend + set `$module`.
- **LogsAudit trait** (`app/Http/Controllers/Concerns/LogsAudit.php`) jadi pola
  untuk semua controller berikutnya, supaya audit log (Aturan #29) konsisten
  tanpa copy-paste `AuditLog::create()` di setiap controller.

## Yang BELUM ada (menyusul Phase 3+)
- Season, Activity, Template SOP (Phase 3–4)
- Capital, Asset, Expense, Debt (Phase 5) — termasuk method
  `Greenhouse::constructionCost()` baru bisa dites nyata setelah tabel `assets` ada
- UI Blade/Livewire — Phase 2 ini API-only, sesuai urutan roadmap
