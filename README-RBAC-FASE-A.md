# RBAC Fase A — Pembatasan "Data Milik Sendiri" (Setup Notes)

## File yang MENIMPA file lama
```
app/Policies/Concerns/AuthorizesByModulePermission.php  (viewAny/view
                                                            kenali view_own)
app/Models/User.php                                      (2 helper baru:
                                                            canViewAllOf,
                                                            canViewOwnOnlyOf)
database/seeders/RolePermissionSeeder.php                 (tambah 3
                                                            permission
                                                            view_own)
app/Http/Controllers/Api/V1/PurchaseController.php        (index() filter
                                                            otomatis)
app/Http/Controllers/Api/V1/SaleController.php             (index() filter
                                                            otomatis)
app/Http/Controllers/Api/V1/ExpenseController.php          (index() filter
                                                            otomatis)
```
File baru: test-nya saja.

## Setelah menyalin
```bash
php artisan db:seed
php artisan test --filter=OwnDataRestrictionTest
```
(`db:seed` aman dijalankan ulang seperti biasa — pakai `firstOrCreate`)

## Cara pakai (nanti dipakai di Fase F untuk role konkret)

Bikin role dengan `purchase.view_own` (BUKAN `purchase.view`) — user itu
otomatis hanya lihat purchase yang dia buat sendiri, baik di daftar
(`GET /purchases`) maupun kalau coba akses detail punya orang lain
(`GET /purchases/{id}` → 403).

Kalau role itu SEKALIGUS punya `purchase.view` (full), sistem
memprioritaskan akses penuh — `view_own` cuma berlaku kalau `view` TIDAK
ada. Jadi tidak perlu takut kombinasi permission yang aneh, hasilnya
selalu masuk akal (akses paling longgar yang menang).

## PENTING — batasan yang saya sengaja TIDAK ubah

- `update` dan `delete` **TIDAK** ikut dibatasi "punya sendiri saja" —
  cuma level modul seperti biasa (`purchase.update`, dst). Kalau nanti
  Anda juga mau "cuma boleh edit/hapus punya sendiri", itu perlu
  perluasan terpisah (belum diminta di skema bisnis Anda, jadi belum
  saya buat, supaya tidak menebak-nebak kebutuhan yang belum jelas).
- Baru diterapkan ke 3 modul: Purchase, Sale, Expense — sesuai
  kebutuhan konkret dari skema bisnis Anda. Modul lain (Greenhouse,
  Season, dst) belum punya `view_own`, jadi perilakunya tidak berubah
  sama sekali (aman, tidak ada regresi).

## Lanjut ke Fase B

Setelah ini teruji jalan di project Anda, saya lanjut ke Fase B
(sembunyikan kolom harga kulakan dari user yang tidak berhak).
