# Phase 9 UI — Halaman Modal (Capital)

Halaman kedua di grup Keuangan.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   ("Modal" jadi link aktif,
                                          2 tempat)
routes/web.php                           (+ route /capital)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=CapitalManageTest
```

## Catatan penting: TIDAK ADA fitur Edit

Ini beda dari halaman lain — sengaja **tidak ada tombol Edit**, cuma
Catat & Hapus. Ini bukan kelupaan, tapi memang dari API-nya sejak
awal (`CapitalTransactionController` cuma punya `store`/`show`/
`destroy`, tidak ada `update`) — sesuai praktik akuntansi yang benar:
begitu transaksi modal dicatat, koreksinya lewat **hapus lalu catat
ulang**, bukan diam-diam mengedit riwayat.

## 3 jenis transaksi
- **Setoran Modal Pemilik** & **Setoran Modal Investor** — uang masuk
- **Penarikan (Prive)** — uang keluar dari perusahaan ke pemilik

## Lanjut

Sisa grup Keuangan: **Hutang, Aset Tetap**. Sisa sidebar lainnya:
**Laporan**.
