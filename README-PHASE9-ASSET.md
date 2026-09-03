# Phase 9 UI — Halaman Aset Tetap (Asset)

Halaman TERAKHIR di grup Keuangan — dengan ini, seluruh grup
Keuangan (Beban, Modal, Hutang, Aset Tetap) lengkap.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   ("Aset Tetap" jadi link
                                          aktif, 2 tempat)
routes/web.php                           (+ route /assets)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=AssetManageTest
```

Halaman ini pola CRUD standar (seperti Greenhouse) — bisa
tambah/edit/hapus penuh, tidak ada batasan khusus seperti Modal
(yang tidak bisa edit) atau Hutang (yang ada 2 modal terpisah).

## SELESAI: Seluruh Grup Keuangan

Beban ✅, Modal ✅, Hutang ✅, Aset Tetap ✅ — grup Keuangan di
sidebar sudah lengkap semua isinya, tidak ada placeholder lagi.

## Sisa dari SELURUH Phase 9

Cuma tinggal **Laporan** — satu-satunya placeholder yang masih
tersisa di sidebar.
