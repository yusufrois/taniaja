# Phase 10 (Bagian 3): Bug #6 — Aset Tetap Sekarang Posting Jurnal Otomatis

## Konfirmasi temuan Anda

Benar — akun **"1400 Aset Tetap"** sudah ada di Bagan Akun standar
sejak Fase L1, tapi tidak pernah ada yang menyalakan posting
otomatisnya (beda dari Modal/Hutang/Beban yang sudah lebih dulu
diotomatiskan sejak Fase L2).

## Solusi

Input Aset Tetap sekarang **otomatis membuat jurnal**: Debit "Aset
Tetap" (1400), Kredit Kas/Bank yang dipilih (atau Kas default kalau
tidak dipilih) — sama persis polanya seperti Modal/Hutang/Beban.
Sekalian dapat manfaat 2 perbaikan sebelumnya secara gratis:
- **Hapus Aset Tetap** otomatis membatalkan jurnalnya juga (fix #7)
- **Aset Tetap yang dicatat sebelum Bagan Akun ada** bisa
  "disembuhkan" lewat tombol "Posting Ulang Transaksi Lama"

## File yang MENIMPA file lama
```
app/Services/Accounting/AccountingPostingService.php   (+ postAsset)
app/Services/Accounting/AccountingBackfillService.php   (+ 'asset')
app/Providers/AppServiceProvider.php                     (+ daftarkan
                                                           AssetObserver)
app/Http/Requests/StoreAssetRequest.php
app/Http/Requests/UpdateAssetRequest.php
app/Models/Asset.php
app/Livewire/Asset/Manage.php
resources/views/livewire/asset/manage.blade.php          (+ dropdown
                                                           "Dibayar
                                                           dari Akun")
resources/views/livewire/chart-of-account/manage.blade.php
                                                           (+ label
                                                           "Aset Tetap"
                                                           di hasil
                                                           backfill)
```
File baru: 1 migration, `app/Observers/AssetObserver.php`, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan view:clear
php artisan test --filter=AssetAutoPostingTest
php artisan test
```

## Cara pakai

Form Aset Tetap sekarang punya field baru **"Dibayar dari Akun"**
(opsional) — pilih Kas/Bank mana yang dipakai beli aset itu, atau
biarkan kosong untuk pakai Kas default.

## Lanjut

Masih ada **#5 (Luas GH otomatis)** dan **#9 (proteksi hapus data
yang masih dipakai)**.
