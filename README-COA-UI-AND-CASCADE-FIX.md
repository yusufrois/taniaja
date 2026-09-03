# Halaman Bagan Akun (Baru) + Perbaikan Greenhouse→Musim Tanam di Beban

## 1. Halaman Bagan Akun — akhirnya ada di UI

**Ini akar masalah "Dibayar dari Akun kosong"**: Bagan Akun cuma
pernah bisa diisi lewat API langsung (Postman) sejak Fase L1 — tidak
pernah ada halamannya di web. Sekarang ada, masuk grup Keuangan
sebagai item PALING ATAS (karena paling fundamental — semua dropdown
"Dibayar dari Akun" di Beban/Modal/Hutang bergantung pada ini).

### File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (+ "Bagan Akun" di grup
                                          Keuangan, 2 tempat)
routes/web.php                           (+ route /chart-of-accounts)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

### Cara pakai
Buka halaman **Bagan Akun** (menu Keuangan) → kalau masih kosong,
klik **"Isi 16 Akun Standar Sekarang"** (aman diklik berkali-kali,
tidak akan duplikat). Setelah itu, semua dropdown "Dibayar dari Akun"
di seluruh aplikasi otomatis terisi.

## 2. Greenhouse → Musim Tanam Saling Terkait di Form Beban

### File yang MENIMPA file lama
```
app/Livewire/Expense/Manage.php
resources/views/livewire/expense/manage.blade.php
```
File baru: 1 test.

Pilih Greenhouse dulu → dropdown Musim Tanam otomatis cuma
menampilkan musim yang ADA di greenhouse itu. Ganti pilihan
Greenhouse → pilihan Musim yang lama otomatis di-reset (supaya tidak
ada kombinasi yang tidak nyambung kesimpan tanpa sadar).

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=ChartOfAccountManageTest
php artisan test --filter=ExpenseGreenhouseSeasonCascadeTest
```

## Soal Kategori Aset yang masih kosong

Halaman **Kategori Aset** sendiri sudah pernah saya kirim
(`taniaja-enhancements-1-3.zip`, sesi sebelumnya) — kalau Anda belum
sempat menerapkan file dari paket itu, tolong dicek lagi. Setelah
diterapkan, buka menu **Kategori Aset** (grup Data Master) dan
tambahkan minimal 1 kategori (misal "Peralatan", "Bangunan") — baru
setelah itu dropdown Kategori di form Aset Tetap akan terisi.

## Lanjut

Dengan ini, seluruh grup Keuangan (termasuk fondasinya, Bagan Akun)
sudah benar-benar lengkap dan bisa dipakai dari UI tanpa perlu
Postman sama sekali. Sisa dari SELURUH Phase 9: cuma **Laporan**.
