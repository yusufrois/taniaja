# Perbaikan UX: Dropdown Metode Bayar + Sidebar Bisa Diminimize

## 1. Metode Pembayaran jadi Dropdown

Sebelumnya ketik bebas, sekarang pilihan: **Tunai, Transfer, Giro,
QRIS**. Tidak perlu perubahan backend — field ini memang sudah teks
bebas sejak awal (tidak ada validasi khusus), jadi cukup ganti
tampilan form-nya saja.

### File yang MENIMPA file lama
```
resources/views/livewire/expense/manage.blade.php
```

## 2. Grup Sidebar Bisa Diminimize (Data Master, Keuangan)

Klik judul grup ("Data Master" / "Keuangan") untuk buka/tutup daftar
di dalamnya — hemat ruang kalau lagi tidak butuh lihat semuanya.
Status buka/tutup per grup independen (bisa buka Data Master, tutup
Keuangan, atau sebaliknya). Berlaku di sidebar desktop MAUPUN menu
mobile.

### File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php
```

Defaultnya **terbuka** (sama seperti sebelumnya) — supaya tidak
mengejutkan yang sudah terbiasa. Kalau nanti mau default TERTUTUP
(supaya hemat ruang sejak awal buka halaman), tinggal bilang, saya
ubah `dataMasterOpen: true` / `keuanganOpen: true` jadi `false` di
baris paling atas layout.

## Setelah menyalin
```bash
php artisan view:clear
```
Hard refresh browser.

## Pola untuk grup baru nanti

Kalau nanti ada grup sidebar baru lagi (misal "Laporan" jadi
beranak beberapa sub-halaman), pola yang sama (tombol + `x-show`
+ state di `x-data` induk) tinggal saya ulangi persis seperti ini.
