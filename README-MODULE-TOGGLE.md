# Fitur Baru: Pengaturan Modul (Petani vs Tengkulak vs Campuran)

Sesuai kesepakatan kita — bukan "pilih tipe usaha saat daftar"
(kaku), tapi **toggle per modul** yang bisa diubah kapan saja tanpa
kehilangan data.

## Cara pakai

Menu **"Pengaturan Modul"** (ikon 🧩) muncul di paling bawah
sidebar — **cuma untuk Owner** (yang punya izin `company.update`).
Di situ ada saklar untuk modul **"Budidaya"** (Greenhouse + Musim
Tanam) — matikan kalau perusahaan Anda murni tengkulak (jual-beli
saja, tidak menanam sendiri).

Begitu dimatikan, menu **Greenhouse** dan **Musim Tanam** otomatis
hilang dari sidebar SEMUA staf di perusahaan itu. Data yang sudah ada
TIDAK terhapus — tinggal nyalakan lagi kapan saja kalau berubah
pikiran.

## Kenapa cuma "Budidaya" dulu

Modul **"Pembelian"** (untuk tengkulak) sengaja belum ditambahkan ke
daftar toggle — soalnya halaman UI-nya sendiri **belum ada** (masih
di roadmap Fase UI-1). Percuma ada saklar untuk sesuatu yang belum
ada bentuknya. Begitu halaman Pembelian jadi, tinggal tambah 1 baris
kode untuk toggle-nya — struktur sudah disiapkan untuk itu (kolom
database sudah JSON, tidak perlu migration baru).

## File yang MENIMPA file lama
```
app/Models/Company.php
resources/views/layouts/app.blade.php   (dibangun ulang dari basis
                                          yang benar-benar lengkap —
                                          lihat catatan di bawah)
routes/web.php
```
File baru: 1 migration, `app/Livewire/Settings/ModuleToggle.php`,
`resources/views/livewire/settings/module-toggle.blade.php`, 1 test.

## PENTING — soal file layout kali ini

Saya sempat menemukan file `app.blade.php` yang saya pakai sebagai
basis kerja itu ternyata **versi lama** (dari sebelum halaman Laporan
sungguhan dibangun) — mirip kejadian `web.reports` hilang sebelumnya.
Saya sudah verifikasi ulang dan pastikan basis yang dipakai kali ini
BENAR-BENAR lengkap (Laporan, Bagan Akun, Kategori Aset, semua grup
Keuangan) sebelum menambahkan fitur baru di atasnya.

## Setelah menyalin
```bash
php artisan migrate
php artisan view:clear
php artisan route:clear
php artisan test --filter=ModuleToggleTest
php artisan test
```
