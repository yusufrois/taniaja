# 2 Perbaikan UX yang Anda Sarankan

## 1. Konfirmasi langsung dari bel notifikasi

Sekarang setiap notifikasi punya:
- **✓** (hijau) — tandai sudah dibaca (cuma muncul kalau belum dibaca)
- **✕** (merah) — hapus, seperti sebelumnya

Khusus notifikasi tipe "staf sudah baca peringatan" — muncul tombol
tambahan **"✓ Konfirmasi Peringatan"** di bawahnya. Klik itu langsung
mengonfirmasi peringatan yang bersangkutan, **tanpa perlu buka Kelola
Staf sama sekali**. Izinnya sama seperti di Kelola Staf: role dengan
`user.warn`, ATAU atasan langsung staf itu.

## 2. Riwayat peringatan diurutkan terbaru di atas

Modal Peringatan di Kelola Staf sekarang menampilkan peringatan
**terbaru dulu** (sebelumnya urutan lama-ke-baru, tidak ideal untuk
lihat riwayat).

## File yang MENIMPA file lama
```
app/Livewire/NotificationBell.php                    (+ confirmWarning)
resources/views/livewire/notification-bell.blade.php  (+ ikon ✓, +
                                                        tombol Konfirmasi)
app/Livewire/Staff/Manage.php                         (urutan riwayat
                                                        terbaru-dulu)
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=NotificationInlineConfirmTest
php artisan test
```
