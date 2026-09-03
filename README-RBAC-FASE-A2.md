# RBAC Fase A2 — Visibilitas Tim/Bawahan (Setup Notes)

Ini penambahan atas Fase A (data milik sendiri) — sekarang ada 3
tingkatan visibilitas: **semua data** → **data tim/bawahan** → **data
milik sendiri**, sesuai info Anda: "Manager bisa lihat data semua
timnya, SPV bisa lihat bawahannya".

## File yang MENIMPA file lama
```
app/Models/User.php                                       (relasi
                                                             supervisor/
                                                             subordinates
                                                             + helper baru)
app/Policies/Concerns/AuthorizesByModulePermission.php     (kenali
                                                             view_team)
database/seeders/RolePermissionSeeder.php                  (tambah 3
                                                             permission
                                                             view_team)
app/Http/Controllers/Api/V1/PurchaseController.php         (index()
                                                             3 tingkat)
app/Http/Controllers/Api/V1/SaleController.php              (index()
                                                             3 tingkat)
app/Http/Controllers/Api/V1/ExpenseController.php           (index()
                                                             3 tingkat)
```
File baru: migration `supervisor_id` + test-nya.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=TeamDataVisibilityTest
```

## Cara pakai

1. **Set siapa atasan siapa** — kolom baru `supervisor_id` di tabel
   `users`. Belum ada endpoint API untuk mengatur ini (menyusul di
   Fase F saat role konkret dibuat) — untuk sekarang bisa diisi
   manual lewat database, atau saya buatkan endpoint kecil kalau mau
   dicoba lebih dulu.
2. **Beri role permission `{module}.view_team`** (bukan `view` atau
   `view_own`) — user itu otomatis lihat data miliknya SENDIRI +
   semua data yang dibuat oleh **bawahan langsungnya** (yang
   `supervisor_id`-nya menunjuk ke dia).

## Batasan yang disengaja

- **Hanya 1 tingkat hierarki** (bawahan langsung), TIDAK rekursif ke
  bawahan-dari-bawahan. Kalau nanti Anda butuh hierarki berlapis
  (Direktur → Manager → SPV → Staff, semua kebawah kelihatan), itu
  perluasan terpisah — sengaja tidak saya buat sekarang karena belum
  jelas dibutuhkan atau tidak untuk skema bisnis Anda.
- **Urutan prioritas jelas**: `view` (semua) > `view_team` (tim) >
  `view_own` (sendiri). Kalau role kebetulan punya lebih dari satu,
  yang paling longgar yang menang — tidak akan pernah membingungkan.
- Diterapkan ke 3 modul yang sama seperti Fase A: Purchase, Sale,
  Expense.

## Test yang membuktikan

- Manager dengan `view_team` melihat punya 2 bawahan langsungnya, TAPI
  **tidak** melihat punya clerk yang bawahan manager LAIN (tim
  sebelah) — ini skenario inti yang Anda sebutkan
- Manager juga lihat data miliknya sendiri, digabung dengan tim
- Akses langsung ke data di luar tim → 403
- Kalau role kebetulan punya `view` DAN `view_team` sekaligus, `view`
  yang menang (lihat semua, bukan cuma tim)

## Lanjut ke Fase B

Setelah ini teruji, saya lanjut ke Fase B (sembunyikan kolom harga
kulakan dari user yang tidak berhak lihat).
