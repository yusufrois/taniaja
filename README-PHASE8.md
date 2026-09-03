# Phase 8 — Laporan (Setup Notes)

## File yang MENIMPA file lama
```
routes/api.php                                   (nambah 5 endpoint laporan)
database/seeders/RoleCapabilitySeeder.php        (tambah report.view untuk Owner/Manager/Finance)
```
Tidak ada perubahan `RolePermissionSeeder` — modul `report` sudah ada di
katalog sejak Phase 1. Semua file lain BARU.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=ReportsTest
```

## Endpoint baru
```
GET  /api/v1/reports/seasons/{id}/hpp
GET  /api/v1/reports/seasons/{id}/profit-loss
GET  /api/v1/reports/greenhouses/{id}/performance
GET  /api/v1/reports/company/profit-loss
GET  /api/v1/reports/activities/today
```
Semua butuh permission `report.view` — hanya dimiliki Owner, Manager, Finance
(Supervisor/Worker sengaja tidak dapat akses laporan, sesuai matrix asli).

## PALING PENTING: definisi angka laporan (baca sebelum ubah formula apapun)

Ini bagian paling rawan salah di seluruh proyek, jadi saya jelaskan detail:

Setiap `Expense` yang dicatat untuk sebuah season **sudah otomatis masuk**
ke `unit_cost` StockBatch begitu dipanen (Phase 6). Saat batch itu terjual,
`SaleItem.cost` MENCATAT PERSIS berapa dari biaya produksi itu yang
"terpakai" oleh penjualan tersebut (Phase 7). Konsekuensinya:

- **`SaleItem.cost` SUDAH merupakan COGS yang presisi** — kalau saya
  jumlahkan `Expense` season itu LAGI sebagai "Operating Expense" di
  laporan P&L, itu akan **double counting** (biaya produksi dihitung 2x).
- Karena itu, formula P&L di Phase 8 ini SENGAJA sederhana dan aman:
  ```
  COGS         = SUM(SaleItem.cost)     ← biaya barang yang SUDAH terjual
  Gross Profit = Revenue - COGS
  Net Profit   = Gross Profit           ← LIHAT catatan di bawah
  ```
- **`net_profit` = `gross_profit` untuk saat ini** — aplikasi ini BELUM
  punya kategori "Operating Expense" yang terpisah dari biaya produksi
  (semua Expense yang dicatat untuk season otomatis jadi bagian cost
  basis batch begitu dipanen). Memisahkan "biaya produksi" vs "overhead
  murni non-produksi" butuh perubahan skema (kategori expense yang lebih
  granular) yang SENGAJA saya tunda daripada membuat formula yang
  keliatan lengkap tapi sebenarnya salah hitung.
- Dua angka tambahan bersifat **informasi saja, BUKAN bagian dari rantai
  profit** — supaya tetap terlihat tanpa risiko ikut kehitung dobel:
  - `production_cost_total` — total semua Expense season/greenhouse/company
    (konteks, bukan pengurang profit langsung)
  - `unsold_inventory_value` — nilai barang yang sudah dipanen/dibeli tapi
    BELUM terjual (`quantity_available × unit_cost` semua batch aktif)

**Cara verifikasi manual**: kalau semua hasil panen sebuah season sudah
terjual habis, maka `production_cost_total` ≈ `COGS + unsold_inventory_value`
(kurang lebih, karena cara alokasi biaya ke batch adalah pendekatan
berjalan/*running approximation*, bukan post-hoc yang presisi sempurna —
lihat catatan di `StockBatchService` Phase 6).

## Bug yang saya temukan dan perbaiki SENDIRI sebelum paket ini dikirim

**Bug tenant-isolation di `greenhousePerformance()`**: query awal saya
pakai `Sale::where('greenhouse_id', X)->orWhereHas('season', ...)` TANPA
dibungkus closure. Ini adalah jebakan klasik Laravel — kombinasi
`where()->orWhereHas()` yang tidak dibungkus bisa membuat kondisi OR
"lolos" dari global scope tenant isolation (`BelongsToCompany`), karena
precedence AND/OR di SQL: `company_scope AND cond1 OR cond2` dibaca
sebagai `(company_scope AND cond1) OR cond2` — bagian `cond2` jadi
TIDAK terikat scope company sama sekali. Sudah saya perbaiki dengan
membungkus kedua kondisi dalam satu closure `where(function($q) {...})`
supaya jadi `company_scope AND (cond1 OR cond2)` yang benar. Saya
temukan ini sendiri lewat review manual sebelum kode ini pernah
dijalankan — jadi tidak ada test yang gagal karena ini, tapi tetap
saya catat di sini demi transparansi penuh.

## Keputusan arsitektur lain

- **`GET /reports/activities/today`** dipisah jadi 2 daftar: `today`
  (jadwal hari ini) dan `overdue` (jadwal lewat tanggal, masih pending)
  — memakai ulang `Schedule::effective_status` yang sudah ada dari
  Phase 4, bukan logika status baru.
- **ROI di `greenhousePerformance`** adalah angka lifetime sederhana
  (`gross_profit ÷ construction_cost × 100`), BUKAN ROI tahunan/per
  periode — anotasi ini ditulis eksplisit di kode supaya tidak disalah-
  pahami sebagai metrik finansial yang lebih canggih dari yang sebenarnya.
- **Alokasi overhead company-wide** (biaya `season_id = NULL`, mis. listrik
  kantor) SENGAJA belum diimplementasikan di Phase 8 ini — sesuai catatan
  arsitektur sejak awal proyek (Bagian 0, poin #5), ini butuh keputusan
  bisnis tentang metode alokasi (equal_split/by_area/by_active_days) yang
  lebih baik dikonfirmasi dulu daripada saya asumsikan sendiri dan berisiko
  salah.

## Yang BELUM ada (menyusul Phase 9-10)
- Dashboard UI (chart, notifikasi in-app) — Phase 9
- Alokasi overhead company-wide ke season (lihat di atas)
- Polish umum (mobile-first form, dst) — Phase 9
- Finalisasi kesiapan Flutter — Phase 10 (sebagian sudah otomatis
  terpenuhi karena API dibangun API-first sejak Phase 1)
