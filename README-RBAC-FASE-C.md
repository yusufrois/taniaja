# RBAC Roadmap Fase C — Stok Input Pertanian (Setup Notes)

## PENTING — dasar file yang dipakai (supaya tidak ada regresi lagi)

Saya belajar dari kejadian sebelumnya (`plant_loss` sempat hilang,
`user.warn` sempat ketimpa) — kali ini saya CEK DULU versi mana yang
paling lengkap sebelum jadi basis edit:
- `RolePermissionSeeder.php` dan `RoleCapabilitySeeder.php` di paket
  ini basisnya dari **agro-rbac-a4**, yang sudah terbukti punya
  `plant_loss` + `cost.view` + `user.warn` sekaligus — bukan dari versi
  Fase B yang ternyata ketinggalan `user.warn`.
- `routes/api.php` basisnya juga dari **agro-rbac-a4** (52 route,
  paling lengkap yang pernah dikirim).

## File yang MENIMPA file lama
```
routes/api.php                                    (tambah endpoint input stock)
database/seeders/RolePermissionSeeder.php         (tambah 3 modul baru)
database/seeders/RoleCapabilitySeeder.php         (assign sesuai skema bisnis)
```
File baru: migration (3 tabel), model (3), policy (3), request (4),
resource (3), controller (3), factory, test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=InputStockTest
```

## Konsep inti

**`InputItem`** = master data pupuk/pestisida/dll (nama, satuan).
**`InputPurchase`** = beli stok dalam jumlah besar (Owner).
**`InputUsage`** = pakai stok di lapangan (Field Officer/Worker).

`current_stock` dihitung LIVE (total beli − total pakai), sama seperti
HST, `current_plant_count`, sisa hutang — bukan kolom tersimpan yang
bisa "ketinggalan sinkron".

## Yang paling penting: otomatis terhubung ke Expense

Setiap kali beli input pertanian (`POST /input-purchases`), sistem
OTOMATIS bikin catatan `Expense` juga — supaya biaya pupuk **ikut
kehitung** di HPP, dashboard musim, dan laporan P&L yang sudah ada,
bukan jadi angka yang "menghilang" di modul terpisah. Ada test khusus
(`test_input_purchase_tied_to_a_season_increases_that_seasons_dashboard_cost`)
yang MEMBUKTIKAN ini — bukan cuma bikin field `expense_id`, tapi benar-benar
menaikkan angka di dashboard musim.

## Cara pakai

**Owner beli pupuk:**
```json
POST /api/v1/input-purchases
{
  "input_item_id": 1,
  "supplier_id": 2,
  "expense_category_id": 3,
  "season_id": 5,
  "purchase_date": "2026-09-01",
  "quantity": 50,
  "unit_price": 20000
}
```
`total_amount` dihitung server (50 × 20.000 = 1.000.000), tidak
dipercaya dari client.

**Field Officer catat pemakaian:**
```json
POST /api/v1/input-usages
{
  "input_item_id": 1,
  "season_id": 5,
  "used_date": "2026-09-05",
  "quantity": 15
}
```
Ditolak (422) kalau jumlahnya melebihi stok yang tersedia.

## Sesuai skema bisnis "petani + tim" Anda

| Role | input_item | input_purchase | input_usage |
|---|---|---|---|
| Owner (petani) | full | full | full |
| Manager | full | full | full |
| Supervisor | view | **tidak ada** | view+create |
| Worker (petugas lapangan) | view | **tidak ada** | view+create |
| Finance | view | view | view |

Supervisor/Worker sengaja TIDAK BISA beli (`input_purchase`) — cuma
Owner/Manager/Finance yang bisa, persis seperti yang Anda jabarkan.

## Yang SENGAJA belum dibuat

- **Integrasi langsung ke Activity/Schedule** (Phase 4) — saat ini
  `InputUsage` punya field `activity_id` opsional untuk dikaitkan
  manual, tapi belum otomatis terpicu saat `Schedule::complete()`
  dipanggil. Menambah itu berarti mengubah alur Phase 4 yang sudah
  stabil — saya tunda sampai benar-benar dibutuhkan, supaya modul ini
  tetap sederhana (Aturan #43).
- **Field `material`/`dosage` di Activity Template** (yang disebut di
  roadmap awal) belum diubah untuk merujuk ke `InputItem` — masih teks
  bebas seperti semula.

## Lanjut ke roadmap

Fase A, A2-A6, B, C semuanya selesai. Berikutnya Fase D (Surat Jalan).
