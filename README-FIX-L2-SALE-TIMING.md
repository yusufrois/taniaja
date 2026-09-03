# Perbaikan Fase L2: Urutan Eksekusi Sale + Celah Quick-Sale

## 2 masalah, 1 paket perbaikan

**1. Bug urutan eksekusi (yang bikin test gagal)**: `Sale::create()`
jalan DULUAN, baris-baris `SaleItem`-nya baru dibuat SETELAHNYA lewat
loop. Observer saya terpicu tepat saat Sale dibuat — saat itu HPP
belum bisa dihitung karena item-nya belum ada. Diperbaiki: posting
akuntansi untuk Sale sekarang dipanggil MANUAL di titik yang tepat
(dalam `SalesService`, setelah semua item selesai dibuat), bukan lewat
Observer otomatis lagi.

**2. Celah yang saya temukan sambil perbaiki (belum ketahuan dari
test Anda)**: jalur "jual cepat" (`StockBatchSale`, Phase 6) ternyata
BELUM saya buatkan jurnalnya sama sekali — beda model dari `Sale`,
jadi tidak ikut ter-cover otomatis. Sekarang sudah ditambahkan.

## File yang MENIMPA file lama
```
app/Services/Sales/SalesService.php                (posting Sale
                                                      dipanggil manual,
                                                      di titik yang tepat)
app/Services/Accounting/AccountingPostingService.php (+ postStockBatchSale())
app/Providers/AppServiceProvider.php                 (Sale::observe()
                                                       DIHAPUS, StockBatchSale::observe()
                                                       DITAMBAH)
```
File baru: 1 Observer (`StockBatchSaleObserver`), 1 test tambahan.

## File yang jadi TIDAK TERPAKAI (aman dibiarkan, tidak perlu dihapus)
```
app/Observers/SaleObserver.php
```
Class ini sudah tidak lagi didaftarkan di `AppServiceProvider` —
boleh dibiarkan saja (tidak berbahaya, cuma dead code), atau dihapus
kalau mau beres-beres.

## Setelah menyalin
```bash
php artisan test --filter=Accounting
```
(filter `Accounting` mencakup SEMUA file test akuntansi: L1, L2, dan
perbaikan ini sekaligus)

## Pelajaran untuk Fase L2 selanjutnya (Payroll, dll)
Kalau nanti Payroll (Payslip) ikut di-auto-post, perlu dicek dulu:
apakah datanya SUDAH LENGKAP di titik model itu 'created'? Kalau ada
child-record yang dibuat SETELAHNYA (seperti SaleItem di sini), pola
yang sama (panggil manual di titik yang tepat, bukan Observer) harus
dipakai lagi.
