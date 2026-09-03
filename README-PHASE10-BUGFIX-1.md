# Phase 10 (Bagian 1): Perbaikan Bug #7 dan #8

## Bug #7 — Hapus Transaksi Tidak Membalikkan Saldo/Laporan

**Penyebab**: sistem sudah punya cara "void" jurnal (dipakai untuk
hapus jurnal manual), tapi tidak pernah terpasang otomatis saat
Beban/Hutang/dll dihapus. Jadi Dashboard (baca langsung dari tabel
Beban) benar berkurang, tapi Laporan/Neraca Saldo (baca dari jurnal)
tetap mencatat angka lama.

**Solusi**: dipasang di **9 jenis transaksi** — begitu dihapus,
jurnalnya otomatis ikut dibatalkan (void):
Beban, Hutang, Cicilan Hutang, Transaksi Modal, Pembelian, Pembayaran
Pembelian, Penjualan, Pembayaran Penjualan, Jual Cepat, Transfer
Antar Akun.

### File yang MENIMPA file lama
```
app/Services/Accounting/AccountingPostingService.php   (+ method
                                                          voidFor)
app/Providers/AppServiceProvider.php                     (+ daftarkan
                                                          SaleObserver)
app/Observers/ExpenseObserver.php
app/Observers/DebtObserver.php
app/Observers/DebtPaymentObserver.php
app/Observers/CapitalTransactionObserver.php
app/Observers/PurchaseObserver.php
app/Observers/PurchasePaymentObserver.php
app/Observers/SalePaymentObserver.php
app/Observers/StockBatchSaleObserver.php
app/Observers/AccountTransferObserver.php
```
File baru: `app/Observers/SaleObserver.php` (baru — Sale sebelumnya
tidak punya observer sama sekali), 1 test.

## Bug #8 — Status Musim Tanam Salah Jadi "Panen"

**Penyebab**: begitu field "Tanggal Panen Aktual" diisi, status
langsung dipaksa jadi "Panen" — **TANPA CEK apakah tanggal itu
sudah lewat atau masih di masa depan**. Kalau Anda salah ketik
(atau memang sengaja isi tanggal rencana), status langsung salah.

**Solusi**: sekarang cuma berubah jadi "Panen" kalau Tanggal Panen
Aktual itu **hari ini atau sudah lewat**. Tanggal di masa depan tidak
lagi memicu perubahan status otomatis.

**Tambahan**: form sekarang menampilkan **pratinjau tanggal dalam
format Indonesia** (misal "→ 04 November 2026") di bawah tiap field
tanggal — supaya Anda bisa langsung cek apakah date picker browser
(yang formatnya mm/dd/yyyy, gampang salah baca) sudah sesuai maksud.
Juga ada validasi baru: Estimasi Panen dan Tanggal Panen Aktual tidak
boleh SEBELUM Tanggal Tanam.

### File yang MENIMPA file lama
```
app/Livewire/Season/Manage.php
resources/views/livewire/season/manage.blade.php
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=VoidJournalOnDeleteTest
php artisan test --filter=SeasonHarvestDateBugTest
php artisan test
```
(filter terakhir sengaja disertakan — pastikan tidak ada test lama
yang rusak akibat 2 perbaikan besar ini)

## Lanjut

Masih ada #5, #6, #9 (Prioritas 2) yang menyusul di paket berikutnya.
