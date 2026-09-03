# Tambahan #1 & #3: Kategori Aset (Master Data) + Beban Approval Berpengaruh ke Laporan

## #3 — Kategori Aset jadi Master Data + Dropdown

### File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php     (+ "Kategori Aset" di grup
                                            Data Master, 2 tempat)
resources/views/livewire/asset/manage.blade.php  (kategori jadi
                                                    dropdown, bukan
                                                    ketik bebas)
app/Livewire/Asset/Manage.php              (+ ambil daftar kategori)
routes/web.php                             (+ route /asset-categories)
routes/api.php                             (+ route API asset-categories)
database/seeders/RolePermissionSeeder.php  (+ modul asset_category)
database/seeders/RoleCapabilitySeeder.php  (assign ke Owner/Manager/
                                             Finance, level sama
                                             seperti modul 'asset')
```
File baru: 1 migration, 1 model, 1 policy, 1 controller API, 1
request, 1 resource, 1 komponen Livewire, 1 view Blade, 1 test.

**Catatan desain**: `Asset.category` TETAP kolom teks biasa (bukan
foreign key) — daftar kategori cuma untuk ngisi pilihan dropdown,
supaya kalau nanti 1 kategori dihapus, aset yang sudah pakai nama itu
tidak jadi rusak/yatim.

## #1 — Beban "Menunggu" Tidak Masuk Hitungan Laporan

### File yang MENIMPA file lama
```
app/Livewire/Dashboard.php                          (3 titik hitungan
                                                       beban)
app/Http/Controllers/Api/V1/ReportController.php    (2 titik: Laba
                                                       Rugi & performa
                                                       greenhouse)
```
File baru: 1 test.

**Yang berubah**: semua hitungan total beban di Dashboard dan Laporan
Laba Rugi sekarang **cuma menghitung yang sudah "✓ Disetujui"** — yang
masih "Menunggu" diabaikan dulu.

**Yang SENGAJA TIDAK diubah**: jurnal akuntansi otomatis (Fase L2)
tetap mencatat begitu Beban dibuat, terlepas dari status approval.
Kalau Anda mau itu JUGA menunggu approval dulu (supaya buku besar dan
laporan benar-benar sinkron), itu perubahan terpisah yang lebih besar
(perlu pindah titik pemicu jurnal dari saat "dibuat" ke saat
"disetujui") — beri tahu kalau itu yang diinginkan.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan view:clear
php artisan test --filter=AssetCategoryManageTest
php artisan test --filter=ApprovedExpenseOnlyTest
```

## Lanjut

Setelah ini teruji jalan, saya lanjutkan ke **#2 — Jadwal cicilan
Hutang otomatis** (permintaan paling besar dari ketiganya) sebagai
paket terpisah.
