# Perbaikan: Bel Notifikasi Hilang + Auto-Refresh

## Penyebab #1: Bel hilang

Sama seperti kasus `web.reports` yang pernah hilang dulu — paket
terakhir saya membangun ulang `app.blade.php` dari basis yang
**belum punya bel notifikasi** (basis itu dari SEBELUM fitur bel
dibangun). Murni kesalahan saya menyalin file dasar yang salah,
bukan disengaja hilangkan.

Sudah saya verifikasi ulang: basis kali ini punya SEMUA fitur
bersamaan — bel notifikasi, izin sidebar per peran, akses Kelola
Staf untuk atasan dengan anak buah, dst.

## Penyebab #2: Kenapa "harus masuk Kelola Staf dulu baru muncul"

Bel notifikasi sebelumnya **tidak auto-refresh** — Livewire tidak
otomatis tahu ada notifikasi baru dari user LAIN (staf) kecuali
komponennya sendiri di-render ulang (misal pindah halaman). Sekarang
ditambahkan **`wire:poll.15s`** — bel akan cek notifikasi baru
otomatis setiap 15 detik, tanpa perlu pindah halaman atau reload
manual.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php           (bel dikembalikan +
                                                  tetap ada semua fix
                                                  sidebar sebelumnya)
resources/views/livewire/notification-bell.blade.php  (+ wire:poll.15s)
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=NotificationBellPresenceTest
php artisan test
```

## Cara coba manual

1. Login sebagai atasan, buka Dashboard — bel 🔔 harus terlihat lagi
   di header
2. Buka 2 browser/tab berbeda: satu sebagai staf, satu sebagai
   atasan
3. Di tab staf, klik "Sudah Baca" pada peringatan
4. Di tab atasan, **tunggu maksimal 15 detik** tanpa reload apapun —
   badge merah di bel harus muncul otomatis
