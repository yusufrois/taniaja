# Phase 10 (Bagian 5, TERAKHIR): Bug #9 — Proteksi Hapus Data yang Masih Dipakai

## Solusi

**7 jenis master data** sekarang tidak bisa dihapus kalau masih
dipakai transaksi lain — baik lewat **API maupun web**:

| Master Data | Diblokir kalau masih dipakai di |
|---|---|
| Kategori Beban | Beban |
| Komoditas | Varietas, Musim Tanam |
| Varietas | Musim Tanam |
| Supplier | Pembelian, Pembelian Pupuk |
| Customer | Penjualan |
| Grade | Hasil Panen |
| Kategori Aset | Aset Tetap (dicocokkan dari nama, bukan ID) |

Pesannya jelas: *"Tidak bisa dihapus — masih dipakai di data
Varietas. Kosongkan atau ganti dulu semua data yang memakainya
sebelum menghapus."*

## Kenapa 2 lapis (API + Livewire)

Tombol hapus di halaman web **memanggil Eloquent langsung**, tidak
lewat API controller — jadi proteksi di API saja TIDAK otomatis
melindungi web. Logic pengecekannya saya taruh di satu Service
(`ReferencedDeletionChecker`, tidak terikat HTTP) yang dipakai
BERSAMA oleh API (lewat trait) dan Livewire — sesuai prinsip Phase
10, satu sumber kebenaran.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/ExpenseCategoryController.php
app/Http/Controllers/Api/V1/CropController.php
app/Http/Controllers/Api/V1/VarietyController.php
app/Http/Controllers/Api/V1/SupplierController.php
app/Http/Controllers/Api/V1/CustomerController.php
app/Http/Controllers/Api/V1/GradeController.php
app/Http/Controllers/Api/V1/AssetCategoryController.php
app/Livewire/Crop/Manage.php
app/Livewire/Variety/Manage.php
app/Livewire/Supplier/Manage.php
app/Livewire/Customer/Manage.php
app/Livewire/Grade/Manage.php
app/Livewire/ExpenseCategory/Manage.php
app/Livewire/AssetCategory/Manage.php
resources/views/livewire/crop/manage.blade.php
resources/views/livewire/variety/manage.blade.php
resources/views/livewire/supplier/manage.blade.php
resources/views/livewire/customer/manage.blade.php
resources/views/livewire/grade/manage.blade.php
resources/views/livewire/expense-category/manage.blade.php
resources/views/livewire/asset-category/manage.blade.php
```
File baru: `app/Services/ReferencedDeletionChecker.php`,
`app/Http/Controllers/Concerns/GuardsAgainstReferencedDeletion.php`,
1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=DeletionGuardTest
php artisan test
```

## Catatan

**ChartOfAccount (Bagan Akun) TIDAK diproteksi di sini** — ternyata
memang belum pernah punya endpoint hapus sama sekali (baik API
maupun web), jadi otomatis aman tanpa perlu tambahan.

---

# SELESAI: Seluruh Prioritas 1 & 2 Phase 10

Dengan ini, semua 9 poin sudah tertangani:
- #1, #2 — sudah dijelaskan/direncanakan (butuh sesi UI terpisah)
- #3, #4 — sudah dijawab langsung (tidak perlu kode)
- #5 — Luas GH otomatis ✅
- #6 — Aset Tetap posting jurnal ✅
- #7 — Hapus transaksi membatalkan jurnal ✅
- #8 — Bug status Musim Tanam + berlaku di API ✅
- #9 — Proteksi hapus data terpakai ✅

Total pekerjaan Phase 10 ini melibatkan **12 paket zip terpisah**
sepanjang sesi ini. Sarankan jalankan `php artisan test` (tanpa
filter) di akhir untuk pastikan semuanya utuh bersamaan.
