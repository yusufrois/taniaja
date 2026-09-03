# Phase 6 — Panen & Pembelian / Stock (Setup Notes)

Fase ini disisipkan atas permintaan Anda: fitur tengkulak (beli dari
petani lain → jual → hitung untung), digabung dengan modul Panen asli
yang memang sudah direncanakan di Phase 6.

## File yang MENIMPA file lama
```
routes/api.php                                   (nambah endpoint Phase 6)
database/seeders/RoleCapabilitySeeder.php        (tambah modul harvest/purchase/sale)
database/seeders/RolePermissionSeeder.php        (tambah modul 'purchase' ke katalog)
app/Models/Expense.php                           (tambah relasi purchase() + purchase_id)
```
Semua file lain BARU — termasuk 3 factory (`GradeFactory`, `SupplierFactory`,
`CustomerFactory`) yang ternyata "utang" sejak Phase 2, baru ketahuan
sekarang saat saya butuh untuk test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=TradingWorkflowTest
```

## Konsep inti: StockBatch

Setiap kali ada barang siap jual — dari panen sendiri ATAU dari beli —
otomatis terbentuk 1 baris `stock_batches` dengan `unit_cost` (biaya per kg)
yang sudah dihitung. Sesuai permintaan Anda, **dipisah jelas** lewat kolom
`source_type`:
- `own_harvest` — biaya per kg dihitung dari total biaya musim tanam ÷ total
  berat panen musim itu sejauh ini (perkiraan HPP berjalan; HPP final yang
  lebih presisi ada di Phase 8)
- `purchased` — biaya per kg = (harga beli total + biaya operasional dagang
  yang dikaitkan) ÷ kuantitas

## Alur kerja tengkulak (yang Anda minta)

1. **Beli dari petani/supplier:**
   `POST /api/v1/purchases` — `{ "supplier_id": 1, "crop_id": 1, "purchase_date": "2026-08-28", "quantity": 200, "unit_price": 8000 }`
   → otomatis bikin StockBatch, `total_amount` dihitung server (200 × 8000 = 1.600.000, tidak dipercaya dari client)

2. **Kaitkan biaya operasional** (transport, sortir, dll) ke pembelian itu:
   `POST /api/v1/expenses` — `{ "purchase_id": 1, "expense_category_id": 1, "date": "...", "amount": 200000, "description": "Ongkos transport" }`
   → biaya ini IKUT dihitung ke `unit_cost` batch (total 1.800.000 ÷ 200kg = Rp9.000/kg)

3. **Cek stok & biaya per kg:**
   `GET /api/v1/stock-batches?source_type=purchased`

4. **Jual, untung langsung dihitung:**
   `POST /api/v1/stock-batches/{id}/sell` — `{ "quantity_sold": 200, "sale_price_per_unit": 12000 }`
   → response berisi `revenue`, `cost`, dan **`profit`** langsung (2.400.000 − 1.800.000 = 600.000)

## Alur kerja Panen (hasil sendiri)

`POST /api/v1/harvests` — body:
```json
{
  "season_id": 1,
  "harvest_date": "2026-08-28",
  "items": [
    { "grade_id": 1, "weight": 300 },
    { "grade_id": 2, "weight": 50 }
  ]
}
```
Satu request langsung membuat Harvest + semua HarvestItem (per grade) +
StockBatch untuk masing-masing item, dalam satu database transaction —
jadi tidak mungkin ada panen yang tercatat tapi stoknya tidak terbentuk.

## Keputusan arsitektur penting

1. **Biaya batch di-freeze saat batch dibuat, tidak dihitung ulang
   otomatis.** Kalau Anda tambah biaya baru ke purchase yang batch-nya
   sudah kadung terjual sebagian, `unit_cost` batch itu TIDAK berubah
   retroaktif — sesuai kebiasaan pembukuan nyata (cost basis dikunci
   saat barang diperoleh, bukan bergerak-gerak setelah sebagian terjual).
2. **Pembayaran ke supplier (Purchase Payment) terpisah dari biaya
   landed cost** — bayar cicilan ke petani tidak mengubah `unit_cost`,
   karena itu soal utang-piutang, bukan biaya barangnya.
3. **`sale` (fitur quick-sell) memakai permission module yang SAMA**
   dengan yang nanti dipakai modul Penjualan resmi (Phase 7) — supaya
   saat Phase 7 dibangun, tidak perlu migrasi ulang hak akses.
4. **StockBatchSale sengaja lebih sederhana** dari modul Sales/Invoice
   resmi (Section 19-21: customer wajib, invoice PDF, multi-item cart).
   Ini jalan pintas cepat untuk kebutuhan "hitung untung" Anda sekarang;
   Phase 7 nanti akan dibangun DI ATAS ledger StockBatch yang sama,
   bukan sistem stok terpisah.

## Kesalahan yang saya perbaiki sendiri sebelum paket ini dikirim
- Ada baris kode sisa (no-op) di `StockBatchService::createFromHarvestItem()`
  yang saya tulis lalu langsung timpa — sudah dibersihkan.
- Ada try/catch kosong yang cuma re-throw exception tanpa efek apa pun
  di `StockBatchController::sell()` — sudah dihapus.

## Yang BELUM ada (menyusul Phase 7+)
- Modul Sales/Invoice resmi dengan Customer wajib, PDF, multi-item cart
- `total_sales` di dashboard musim masih 0 (StockBatchSale belum
  dikaitkan ke Season — akan disatukan saat Sales resmi dibangun)
- HPP presisi + alokasi overhead company-wide (Phase 8)
