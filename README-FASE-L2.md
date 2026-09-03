# Roadmap Tambahan Fase L2 — Auto-Jurnal dari Transaksi yang Sudah Ada

## PALING PENTING — baca ini dulu

Modul yang sudah ada (Jual/Beli/Expense/dst) sekarang **otomatis**
bikin jurnal setiap kali ada transaksi baru — TIDAK perlu input
manual lagi untuk transaksi yang sudah dikenali. **Tampilan/endpoint
modul-modul itu TIDAK BERUBAH SAMA SEKALI** — cuma nambah proses di
belakang layar.

## File yang MENIMPA file lama
```
app/Providers/AppServiceProvider.php   ⚠️ LIHAT PERINGATAN DI BAWAH
app/Http/Controllers/Api/V1/JournalEntryController.php  (+ filter
                                                           reference_type)
```

### ⚠️ PERINGATAN soal `AppServiceProvider.php`

Saya **belum pernah** menyentuh file ini sepanjang proyek — jadi saya
kirim versi standar Laravel + tambahan saya. **KALAU Anda sudah punya
kode custom lain di `boot()`/`register()` file itu (jarang terjadi,
tapi mungkin), JANGAN timpa langsung** — gabung manual: cukup tambahkan
8 baris `Model::observe(...)` di dalam `boot()` yang sudah ada, jangan
timpa seluruh file. Kalau Anda belum pernah edit file ini sendiri,
aman langsung timpa.

File baru: 1 service (`AccountingPostingService`), 8 Observer,
1 test.

## Setelah menyalin
```bash
php artisan test --filter=AutoPostingTest
```
(tidak perlu migration/seed baru — semua tabel sudah ada dari L1)

## Yang paling penting: TIDAK PERNAH memblokir transaksi asli

Ini jaminan paling krusial di seluruh Fase L2: **kalau Bagan Akun
perusahaan belum di-setup** (company lama yang belum sempat jalankan
`seed-defaults`), jurnal otomatis akan **gagal diam-diam** (dicatat
di log, bukan error) — TAPI Jual/Beli/Expense/dst **tetap berhasil
seperti biasa**. Sudah ada test khusus
(`test_creating_a_sale_succeeds_even_without_chart_of_accounts`) yang
membuktikan ini SEBELUM paket ini dikirim, bukan nanti ditemukan lewat
laporan Anda kalau sampai gagal.

## Pemetaan jurnal otomatis

| Transaksi | Debit | Kredit |
|---|---|---|
| Sale (Jual) | Piutang Usaha (1200) | Pendapatan Penjualan (4100) |
| Sale (kalau ada COGS) | + HPP (5100) | + Persediaan Barang Dagang (1300) |
| SalePayment | Kas (1100) | Piutang Usaha (1200) |
| Purchase (Beli) | Persediaan Barang Dagang (1300) | Hutang Usaha (2100) |
| PurchasePayment | Hutang Usaha (2100) | Kas (1100) |
| Expense | Beban Operasional (5200) | Kas (1100) |
| Debt (pinjam) | Kas (1100) | Hutang Bank (2200) |
| DebtPayment | Hutang Bank (2200) | Kas (1100) |
| Capital (setor modal) | Kas (1100) | Modal Pemilik (3100) |
| Capital (prive/tarik) | Modal Pemilik (3100) | Kas (1100) |

**Bonus otomatis**: karena `InputUsage` (Fase C revisi) juga membuat
`Expense` lewat jalur yang sama, pemakaian pupuk **otomatis ikut
terjurnal juga** — tidak perlu kerjaan tambahan.

## Cara kerja teknis (untuk yang penasaran)

Saya pakai **Eloquent Observer** (bukan manggil manual di tiap
controller) — supaya SEMUA jalur pembuatan Sale/Purchase/dst kejaring
otomatis, termasuk jalur yang mungkin belum ada sekarang tapi dibuat
nanti. Kalau saya taruh logic-nya di dalam controller satu-satu,
gampang kelupaan di jalur yang baru.

## Cek jurnal dari 1 transaksi tertentu
```
GET /api/v1/journal-entries?reference_type=sale&reference_id=5
```

## Yang SENGAJA belum dibuat
- **Payroll (Payslip) belum ikut auto-posting** — ditunda ke follow-up
  terpisah supaya paket ini tidak melebar lagi. Kalau mau, tinggal
  bikin `PayslipObserver` dengan pola sama persis.
- **Asumsi Expense selalu tunai** (Debit Beban, Kredit Kas langsung) —
  kalau nanti Expense juga perlu status "belum dibayar" seperti
  Purchase/Sale, itu perluasan terpisah.

## Lanjut ke roadmap
L1, L2 selesai. Berikutnya L3 (Buku Besar & Neraca Saldo).
