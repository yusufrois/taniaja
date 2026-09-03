# Fitur: Posting Ulang Transaksi Lama (Solusi Laporan Kosong)

## Penyebab pasti — sudah dikonfirmasi

Transaksi (Beban, Hutang, dll) yang Anda catat **sebelum** Bagan Akun
ada, tetap berhasil tersimpan (memang sengaja didesain begitu sejak
Fase L2 — supaya Bagan Akun yang belum siap tidak menghalangi
pencatatan transaksi sehari-hari). Tapi karena tidak ada akun untuk
dicatat ke jurnal, **jurnalnya tidak pernah tercipta** — jadi
Laporan (yang 100% baca dari jurnal) jadi kosong meski datanya ada.

## Solusi

Tombol baru **"Posting Ulang Transaksi Lama"** di halaman Bagan Akun
— mencari SEMUA transaksi (Beban, Pembelian, Penjualan, Hutang,
Modal, dst) yang belum pernah dapat jurnal, lalu memprosesnya
sekarang juga. Aman diklik berkali-kali — cuma memproses yang
benar-benar belum pernah diposting, tidak akan duplikat.

## File yang MENIMPA file lama
```
app/Livewire/ChartOfAccount/Manage.php
resources/views/livewire/chart-of-account/manage.blade.php
```
File baru: `app/Services/Accounting/AccountingBackfillService.php`, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=AccountingBackfillTest
```

## Cara pakai
1. Buka halaman **Bagan Akun**
2. Pastikan sudah klik **"Isi Akun Standar"** dulu (kalau belum)
3. Klik **"Posting Ulang Transaksi Lama"**
4. Akan muncul ringkasan: berapa transaksi tiap jenis yang baru
   diposting (misal "Beban: 3 transaksi, Hutang: 1 transaksi")
5. Buka halaman **Laporan** — sekarang seharusnya sudah terisi

## Yang dicakup

Beban, Pembelian, Pembayaran ke Petani, Penjualan, Pembayaran dari
Customer, Jual Cepat, Hutang, Cicilan Hutang, Transaksi Modal,
**Transfer Antar Akun** — semua jenis transaksi yang pernah dibuat
sepanjang Fase L2/L5.
