# Roadmap Tambahan Fase J & K — Laporan Traceability & Riwayat Petani

## File yang MENIMPA file lama
```
app/Models/Season.php                    (+ relasi harvests(), yang
                                           ternyata belum pernah ada
                                           sejak Phase 6)
app/Models/Purchase.php                  (+ relasi creator())
app/Http/Controllers/Api/V1/ReportController.php  (+ 2 method baru)
routes/api.php                           (+ 2 route laporan)
```
File baru: 1 test.

**TIDAK ada perubahan permission/seeder** — kedua laporan ini cuma
butuh `report.view` (sudah ada sejak Phase 8) dan `cost.view` (sudah
ada sejak Fase B), tidak ada modul permission baru.

## Setelah menyalin
```bash
php artisan test --filter=TraceabilityReportsTest
```

## Endpoint baru

**Fase J — Traceability Panen → Jual:**
```
GET /api/v1/reports/seasons/{season}/traceability
```
Menyatukan Season → Harvest → StockBatch → Sale (baik lewat Invoice
penuh MAUPUN quick-sale) jadi satu tampilan: musim ini panen berapa,
terjual ke siapa saja (via jalur mana), sisa stok berapa.

**Fase K — Riwayat per Petani/Tengkulak:**
```
GET /api/v1/reports/suppliers/{supplier}/history
```
Semua riwayat pembelian dari 1 petani/supplier: tanggal, jumlah,
harga (kalau `cost.view`), status lunas/hutang, dan **siapa staff
yang beli** — persis sesuai yang Anda minta di poin 6.

## Yang saya temukan sambil kerjakan ini

Dua relasi Eloquent yang **seharusnya sudah ada sejak lama** ternyata
belum pernah dibuat:
- `Season::harvests()` — padahal `Harvest.season_id` sudah ada sejak
  Phase 6
- `Purchase::creator()` — padahal `Purchase.created_by` juga sudah
  ada sejak Phase 6

Keduanya sekarang saya tambahkan. Ini murni relasi tambahan (tidak
mengubah data/kolom apapun), aman ditambahkan kapan saja.

## Kedua jalur penjualan ditelusuri sekaligus

Sejak Phase 6-7, ada 2 cara barang terjual: **Invoice penuh** (lewat
`Sale`/`SaleItem`) dan **jual cepat** (lewat `StockBatchSale`).
Laporan Fase J ini **menelusuri KEDUANYA** — kalau Anda pakai
campuran keduanya, laporan tetap akurat menampilkan semua, bukan
cuma salah satu jalur.

## Lanjut ke roadmap

Fase G, H, I, J, K selesai. Berikutnya Fase L (Akuntansi
Double-Entry) atau Fase D/E (Surat Jalan, Nota) — mana saja yang mau
duluan.
