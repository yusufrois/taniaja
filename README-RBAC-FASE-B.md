# RBAC Fase B — Sembunyikan Kolom Harga/Biaya (Setup Notes)

## PENTING — perbaikan regresi dari Fase A3

Sambil kerjakan ini, saya sadar `RoleCapabilitySeeder.php` yang dikirim
di paket **Fase A3 kemarin ternyata salah versi** — tidak sengaja copy
dari versi SEBELUM fitur Tanaman Mati (`plant_loss`) ditambahkan,
sehingga 4 baris `plant_loss` hilang dari file itu. Sudah saya
kembalikan di paket ini sekaligus (bukti: cari kata `plant_loss` di
file baru, harus muncul 4 kali).

**Dampak ke Anda**: karena seeder selalu pakai `syncWithoutDetaching`
(cuma nambah, tidak pernah mencabut), data yang SUDAH ADA di database
Anda tidak terpengaruh — permission `plant_loss` yang sudah ter-assign
sebelumnya tetap ada. Regresi ini baru akan kelihatan kalau ada
company BARU yang didaftarkan sesudah paket Fase A3 kemarin dan
sebelum paket ini — role di company itu tidak akan dapat izin
`plant_loss`. Kalau Anda sempat bikin company baru di antara waktu itu,
kabari saya, saya bantu perbaiki role-nya secara manual.

## File yang MENIMPA file lama
```
database/seeders/RolePermissionSeeder.php     (tambah permission cost.view)
database/seeders/RoleCapabilitySeeder.php     (plant_loss dikembalikan
                                                + cost.view ditambahkan)
app/Http/Resources/PurchaseResource.php       (sembunyikan harga beli)
app/Http/Resources/StockBatchResource.php     (sembunyikan unit_cost)
app/Http/Resources/SaleItemResource.php       (sembunyikan cost/profit)
app/Http/Resources/SaleResource.php           (sembunyikan total_profit)
```
File baru: test-nya saja.

## Setelah menyalin
```bash
php artisan db:seed
php artisan test --filter=CostVisibilityTest
```

## Cara pakai

Beri role permission **`cost.view`** (satu permission generik, bukan
per-modul) — otomatis bisa lihat SEMUA angka biaya/margin di seluruh
aplikasi: harga beli dari petani (Purchase), `unit_cost` StockBatch,
`cost`/`profit` per item penjualan, `total_profit` per invoice.

**Tanpa** `cost.view`, field-field itu **HILANG TOTAL** dari response
(bukan `null` atau `Rp 0`) — supaya tidak ada yang salah tafsir "harga
beli Rp 0" padahal sebenarnya cuma disembunyikan.

## Yang TETAP terlihat tanpa cost.view (sesuai skema bisnis Anda)

- Harga JUAL ke customer (`price`, `subtotal`, `total` di Sale) — User
  B tetap bisa pakai harga jual yang sudah ditentukan untuk bikin Surat
  Jalan/invoice
- Status pembayaran customer (`total_paid`, `remaining`,
  `payment_status` di Sale) — ini soal customer bayar berapa, BUKAN
  biaya/margin perusahaan
- Kuantitas barang (di Purchase maupun StockBatch)

## Kenapa 1 permission, bukan per-modul

Awalnya saya sempat pertimbangkan `purchase.view_cost` terpisah dari
`sale.view_cost`, tapi itu berarti Owner harus assign 2+ permission
buat 1 konsep yang sama ("boleh lihat biaya/margin atau tidak"). Satu
`cost.view` lebih sederhana dan konsisten — sesuai Aturan #43 (jangan
overengineering).

## Lanjut ke roadmap

Fase A, A2, A3, B semuanya sekarang selesai. Selanjutnya Fase C (stok
input pertanian/pupuk).
