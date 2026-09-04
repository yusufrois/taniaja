# Fitur Baru: Bel Notifikasi (dari Nol) + Hapus Notifikasi & Peringatan

## Temuan penting

**Belum ada tampilan notifikasi di web sama sekali sebelumnya** —
cuma ada API mentahnya (`GET /notifications`, tandai dibaca). Jadi
selain menambah "hapus", saya bangun sekalian bel notifikasinya.

## 1. Bel Notifikasi (🔔) — Baru

Muncul di header, di semua halaman. Klik untuk buka daftar
notifikasi terbaru:
- Badge merah menunjukkan jumlah yang belum dibaca
- Klik 1 notifikasi = tandai sudah dibaca
- Tombol **✕** per notifikasi = hapus satu
- Tombol **"Bersihkan yang sudah dibaca"** = hapus semua yang sudah
  dibaca sekaligus (ini jawaban langsung untuk "supaya tidak semakin
  banyak")

## 2. Hapus Peringatan (Kelola Staf)

Di modal Peringatan (Kelola Staf), sekarang ada tombol **✕** di
setiap peringatan — untuk koreksi kalau ada yang salah catat. Perlu
izin `user.warn` yang sama seperti yang dipakai untuk memberi
peringatan.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/NotificationController.php  (+ destroy,
                                                           + clearRead)
app/Http/Controllers/Api/V1/UserController.php            (+ deleteWarning)
routes/api.php                                             (+ 3 route)
app/Livewire/Staff/Manage.php                              (+ deleteWarning)
resources/views/livewire/staff/manage.blade.php            (+ tombol ✕)
resources/views/layouts/app.blade.php                      (+ pasang
                                                              bel notifikasi)
```
File baru: `app/Livewire/NotificationBell.php`,
`resources/views/livewire/notification-bell.blade.php`, 2 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan route:clear
php artisan test --filter=NotificationBellTest
php artisan test --filter=StaffManageWarningDeleteTest
php artisan test
```

## Yang belum termasuk

Bel ini **tidak auto-refresh** (perlu buka/tutup dropdown atau muat
ulang halaman untuk lihat notifikasi baru) — bisa ditambahkan
polling/real-time nanti kalau dibutuhkan.
