# Roadmap Tambahan Fase L5 — Kas & Bank sebagai Fitur Pemakai

Penutup roadmap Akuntansi (L1-L5) — memungkinkan banyak akun Kas/Bank,
setiap pembayaran bisa pilih akun mana yang dipakai, dan transfer
antar akun.

## File yang MENIMPA file lama
```
app/Models/SalePayment.php
app/Models/PurchasePayment.php
app/Models/DebtPayment.php
app/Models/CapitalTransaction.php
app/Models/Debt.php
app/Models/Expense.php
app/Http/Requests/StoreSalePaymentRequest.php
app/Http/Requests/StorePurchasePaymentRequest.php
app/Http/Requests/StoreDebtPaymentRequest.php
app/Http/Requests/StoreDebtRequest.php
app/Http/Requests/StoreCapitalTransactionRequest.php
app/Http/Requests/StoreExpenseRequest.php
app/Services/Accounting/AccountingPostingService.php
app/Providers/AppServiceProvider.php
routes/api.php
```
File baru: 2 migration, 2 model (`AccountTransfer` + Observer),
1 policy, 1 request, 1 resource, 1 controller, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan test --filter=CashAndBankTest
```
(migration baru menambah kolom `chart_of_account_id` — nullable, jadi
AMAN dijalankan di database yang sudah berisi data, tidak akan merusak
apapun)

## Yang berubah — 3 hal inti

**1. Banyak akun Kas/Bank** — sudah bisa dari L1 sebenarnya (tinggal
`POST /chart-of-accounts` dengan `type: asset`), cuma sekarang benar-
benar TERPAKAI karena poin 2 di bawah.

**2. Pembayaran bisa pilih akun** — field baru `chart_of_account_id`
(opsional) di:
```
POST /sales/{id}/payments
POST /purchases/{id}/payments
POST /debts/{id}/payments
POST /expenses
POST /debts
POST /capital-transactions
```
Kalau tidak diisi, otomatis pakai Kas (1100) seperti sebelumnya — jadi
**semua kode/integrasi lama yang belum tahu field ini tetap jalan
normal**, tidak ada yang rusak.

**3. Transfer antar akun** — fitur baru:
```
POST /account-transfers
{
  "from_account_id": 1,
  "to_account_id": 2,
  "amount": 500000,
  "date": "2026-09-10",
  "notes": "Setor tunai ke bank"
}
```
Otomatis jadi jurnal (Debit tujuan, Kredit asal) — tidak mengubah
total aset perusahaan, cuma pindah antar "kantong".

## Saldo per akun

Tidak ada endpoint baru untuk ini — **sudah otomatis kepakai** dari
Fase L3 (`GET /accounting/ledger/{id}`, `GET /accounting/trial-balance`).
Begitu Anda punya 3 akun Kas/Bank berbeda, ketiganya otomatis muncul
terpisah di laporan itu, masing-masing dengan saldo sendiri.

## Yang SENGAJA belum diperluas

- **`StockBatchSale`** (jual cepat, Phase 6) masih pakai Kas (1100)
  default, belum bisa pilih akun — beda model dari pembayaran-
  pembayaran lain, butuh migrasi/kerjaan terpisah kalau mau
  diperluas juga.
- **`InputUsage`** (pemakaian pupuk, Fase C) yang otomatis bikin
  Expense — tetap pakai default Kas, tidak sekalian dikasih pilihan
  akun (bisa ditambah kalau dibutuhkan).

## SELESAI: Seluruh roadmap Akuntansi (L1-L5)

Dengan ini, seluruh fondasi akuntansi yang direncanakan dari awal
sudah lengkap: Bagan Akun, Jurnal manual+otomatis, Buku Besar, Neraca
Saldo, Neraca, Laba Rugi, dan sekarang dukungan multi-akun Kas/Bank +
transfer.

**Sisa dari SELURUH roadmap besar sejak awal percakapan ini: Fase F**
(rangkai jadi role siap pakai) — bagian PALING AKHIR.
