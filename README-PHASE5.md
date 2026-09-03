# Phase 5 — Keuangan (Setup Notes)

## File yang MENIMPA file lama
```
routes/api.php                                   (nambah endpoint Phase 5)
database/seeders/RoleCapabilitySeeder.php        (tambah modul capital/asset/expense/debt)
app/Models/Season.php                            (tambah relasi expenses())
app/Models/Greenhouse.php                        (constructionCost() disederhanakan, Asset sudah nyata)
app/Http/Controllers/Api/V1/SeasonController.php (dashboard: total_cost sekarang nyata)
```
Semua file lain BARU, termasuk `database/factories/ExpenseCategoryFactory.php`
yang sebenarnya "utang" dari Phase 2 (waktu itu terlewat tidak dibuat).

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=FinanceModulesTest
```

## Alur kerja Phase 5

**Modal (Capital):**
`POST /api/v1/capital-transactions` — `{ "type": "owner_investment", "date": "2026-01-01", "amount": 50000000 }`

**Asset (termasuk biaya pembangunan greenhouse):**
`POST /api/v1/assets` — `{ "greenhouse_id": 1, "name": "Rangka GH-A", "category": "greenhouse_construction", "purchase_date": "2026-01-01", "value": 15000000 }`
→ setelah ini, `GET /api/v1/greenhouses` akan menunjukkan `construction_cost` terisi otomatis (dijumlah dari semua asset kategori `greenhouse_construction` milik greenhouse itu).

**Pengeluaran (Expense) + approval:**
```
POST /api/v1/expenses          — input pengeluaran (Supervisor/Finance/Manager/Owner bisa)
POST /api/v1/expenses/{id}/approve  — approve (HANYA Finance/Manager/Owner, Supervisor 403)
```

**Hutang (Debt) + pembayaran:**
```
POST /api/v1/debts                    — catat hutang baru
POST /api/v1/debts/{id}/payments      — bayar (bisa dicicil, tidak boleh melebihi sisa)
GET  /api/v1/debts/{id}                — lihat status: unpaid/partial/paid/overdue (dihitung live)
```

## Keputusan arsitektur penting

1. **`Debt::status` dihitung live** dari total pembayaran vs jatuh tempo —
   pola yang sama seperti HST (Phase 3) dan `overdue` di Schedule (Phase 4).
   Tidak ada kolom `status` yang bisa "ketinggalan sinkron".
2. **Pembayaran hutang tidak boleh melebihi sisa** — divalidasi eksplisit
   di `StoreDebtPaymentRequest`, karena kalau dibiarkan, `remaining` bisa
   jadi negatif yang tidak masuk akal secara bisnis.
3. **Expense (input) vs Expense (approve) adalah 2 permission terpisah**
   — `ExpensePolicy::approve()` beda dari `update()` bawaan, supaya
   Supervisor bisa input pengeluaran lapangan tapi tetap butuh Finance/
   Manager/Owner untuk menyetujui, sesuai Role & Permission Matrix.
   Sebuah expense TETAP dihitung ke `total_cost` season meskipun belum
   di-approve — approval itu checkpoint pembukuan, bukan gerbang angka.
4. **`total_cost` di dashboard musim BELUM termasuk alokasi biaya
   company-wide** (listrik, admin, dll dengan `season_id` NULL) — itu
   sengaja ditunda ke Phase 8 sebagai *enhancement laporan* sesuai
   Catatan Arsitektur #5 di awal proyek, supaya Phase 5 tetap sederhana.
5. **`Greenhouse::constructionCost()` akhirnya "hidup"** — sejak Phase 1
   method ini selalu return 0 karena `Asset` belum ada. Sekarang dengan
   Asset model nyata, greenhouse yang punya asset berkategori
   `greenhouse_construction` akan menunjukkan biaya pembangunan asli.

## Kesalahan yang saya perbaiki sendiri sebelum paket ini dikirim
Saat menulis `DebtController::index()`, saya sempat salah ketik
`withoutGlobalScopes(['...SoftDeletingScope'])` yang efeknya akan
menampilkan hutang yang sudah dihapus (soft-deleted) — sudah dihapus
sebelum paket ini di-zip. Disebutkan di sini demi transparansi, bukan
sesuatu yang perlu Anda lakukan apa pun.

## Yang BELUM ada (menyusul Phase 6+)
- Harvest + Grade breakdown (Phase 6) — `total_harvest_kg` masih 0
- Sales + Invoice + Payment (Phase 7) — `total_sales` masih 0
- HPP, P&L lengkap, alokasi overhead company-wide (Phase 8)
