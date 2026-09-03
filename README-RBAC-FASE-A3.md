# RBAC Fase A3 — Owner Bisa Tambah User (Setup Notes)

Ini fondasi yang ternyata terlewat sejak Phase 1 — sebelumnya TIDAK ADA
cara bagi Owner untuk menambah staff (User A/B/C) ke perusahaannya
sendiri lewat API. Satu-satunya cara user tercipta selama ini: registrasi
company (jadi Owner) atau lewat seeder demo.

## File yang MENIMPA file lama
```
routes/api.php                                    (tambah endpoint /users)
database/seeders/RoleCapabilitySeeder.php         (Owner dapat izin user.*)
```
Semua file lain BARU.

## Setelah menyalin
```bash
php artisan db:seed
php artisan test --filter=UserManagementTest
```

## Endpoint baru
```
GET/POST            /api/v1/users
GET/PUT/DELETE      /api/v1/users/{id}
```
Hanya Owner yang dapat izin (`user.view/create/update/delete`) secara
default — bisa diberikan ke role lain lewat `RoleCapabilitySeeder` kalau
mau (mis. Manager juga boleh tambah staff).

## Cara pakai

**Tambah staff baru:**
```json
POST /api/v1/users
{
  "name": "Budi Kulakan",
  "email": "budi@ladangwohijo.test",
  "password": "password123",
  "role_id": 5,
  "supervisor_id": 2
}
```
`role_id` WAJIB dari role yang sudah ada di company yang sama (dicek
otomatis, ditolak 422 kalau dari company lain). `supervisor_id`
opsional — ini yang dipakai fitur "lihat data tim" dari Fase A2.

**Nonaktifkan staff** (bukan hapus permanen):
```
DELETE /api/v1/users/{id}
```
User itu langsung tidak bisa login lagi (403 saat coba login), tapi
riwayat datanya (Purchase/Sale/Expense yang pernah dia buat) tetap utuh
— sama prinsipnya dengan transaksi keuangan lain di app ini (tidak
pernah dihapus permanen, cuma "dimatikan").

## PALING PENTING — kenapa ini controller paling rawan di seluruh app

Model `User` **sengaja TIDAK PAKAI** trait `BelongsToCompany` (keputusan
dari Phase 1, supaya Super Admin bisa akses lintas company). Artinya:
**tenant isolation di controller ini TIDAK OTOMATIS** — beda dari
hampir semua controller lain di aplikasi ini yang otomatis aman lewat
global scope. Saya tambahkan pengecekan manual (`abort_if($user->
company_id !== auth()->user()->company_id, 404)`) di `show()`,
`update()`, `destroy()`.

**Peringatan untuk siapapun yang edit controller ini nanti** (termasuk
saya sendiri di masa depan): jangan pernah hapus baris `abort_if`
itu dengan alasan "sudah dicek policy" — Policy (`UserPolicy`) CUMA
cek permission (`user.update`, dst), TIDAK cek company. Kalau baris
`abort_if` dihapus, Owner company A bisa lihat/ubah/nonaktifkan staff
company B. Sudah ada test khusus (`test_owner_cannot_see_or_manage_users_from_a_different_company`)
yang akan langsung gagal kalau ini sampai rusak.

## Lanjut ke roadmap

Setelah ini teruji, giliran Fase B (sembunyikan kolom harga kulakan).
