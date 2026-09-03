# Phase 7 — Penjualan (Setup Notes)

## File yang MENIMPA file lama
```
routes/api.php                                   (nambah endpoint Phase 7)
app/Models/Season.php                            (tambah relasi sales())
app/Http/Controllers/Api/V1/SeasonController.php (dashboard: total_harvest_kg
                                                    DAN total_sales sekarang nyata)
```
Tidak ada perubahan di `RoleCapabilitySeeder`/`RolePermissionSeeder` —
modul `sale` sudah lengkap sejak Phase 6 dan dipakai ulang di sini.
Semua file lain BARU.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=SalesWorkflowTest
```

## Alur kerja Penjualan resmi

```json
POST /api/v1/sales
{
  "customer_id": 1,
  "season_id": 3,
  "date": "2026-08-28",
  "due_date": "2026-09-15",
  "items": [
    { "stock_batch_id": 5, "description": "Melon Grade A", "quantity": 100, "unit": "kg", "price": 30000 },
    { "description": "Jasa packing", "quantity": 1, "price": 50000 }
  ]
}
```
- `invoice_number` dibuat otomatis: `INV/{kode_company}/{YYYYMM}/{urutan}`, unik per company
- Item yang punya `stock_batch_id` otomatis mengurangi stok batch itu DAN
  menghitung `cost`/`profit` per baris (pakai `unit_cost` batch saat itu)
- Item tanpa `stock_batch_id` (mis. jasa) tidak mempengaruhi stok, cost = 0
- `subtotal`, `total` dihitung server, tidak dipercaya dari client (Aturan #35)

**Pembayaran (bisa dicicil):**
`POST /api/v1/sales/{id}/payments` — sama persis polanya dengan Debt Payment
(Phase 5) dan Purchase Payment (Phase 6): tidak boleh melebihi sisa tagihan.

## Perbaikan yang saya temukan sambil kerjakan Phase 7

Saat mengedit ulang `SeasonController.php` untuk `total_sales`, saya sadar
di Phase 6 kemarin **saya lupa mengisi `total_harvest_kg`** meski modul
Panen sudah ada — masih tertinggal hardcode `0`. Sudah ikut diperbaiki di
paket ini sekalian (karena filenya memang harus ditimpa ulang untuk
`total_sales`), tidak perlu langkah tambahan dari Anda.

## Keputusan arsitektur penting

1. **Sale dan StockBatchSale (Phase 6) hidup berdampingan**, bukan yang
   satu menggantikan yang lain. Keduanya menarik dari `stock_batches`
   yang SAMA (satu sumber kebenaran stok), tapi:
   - `StockBatchSale` (Phase 6) = jalan pintas cepat, tanpa customer wajib,
     cocok untuk transaksi tengkulak yang cepat
   - `Sale` (Phase 7) = invoice resmi, multi-item, customer wajib,
     nomor invoice otomatis, siap untuk PDF nanti
2. **Nomor invoice: retry otomatis kalau tabrakan.** Nomor urut dihitung
   dari jumlah invoice bulan itu (bukan tabel counter terpisah — lebih
   sederhana sesuai Aturan #43), tapi ini punya celah race condition
   kecil kalau 2 request nyaris bersamaan. Solusinya: kalau constraint
   unique di database menolak (`company_id`+`invoice_number` bentrok),
   sistem otomatis coba nomor berikutnya, sampai 3 kali percobaan.
3. **Menghapus Sale TIDAK mengembalikan stok batch secara otomatis** —
   sesuai Aturan #48 (correctness/data integrity dulu): invoice yang
   dibatalkan di pembukuan belum tentu berarti barangnya fisik balik ke
   gudang. Restock harus jadi aksi sadar terpisah (belum ada di MVP ini).
4. **`total_sales` di dashboard musim HANYA dari Sale resmi**, tidak
   termasuk `StockBatchSale` (quick-sell Phase 6) — karena quick-sell
   tidak selalu terikat ke satu musim tanam tertentu (bisa jualan hasil
   dagang/tengkulak yang tidak ada season-nya).

## Yang BELUM ada (menyusul Phase 8+)
- Invoice PDF (Section 20) — response API sudah lengkap datanya, tinggal
  di-render ke PDF, belum dikerjakan di fase ini
- HPP presisi, P&L lengkap per company/greenhouse/season, alokasi
  overhead company-wide (Phase 8) — ini fase laporan terakhir sebelum
  UI/UX polish (Phase 9) dan finalisasi API (Phase 10)
