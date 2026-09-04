# Fitur Baru: Alur "Sudah Baca" → Konfirmasi Atasan untuk Peringatan

## Alur lengkap

1. Staf lihat peringatan di Dashboard-nya, klik **"Sudah Baca"**
2. Status berubah jadi **"Menunggu konfirmasi atasan"** — masih
   tampil di Dashboard staf (belum hilang)
3. Atasan (siapa pun dengan izin `user.warn`) buka **Kelola Staf** →
   Peringatan → lihat status "Staf sudah baca, menunggu konfirmasi
   Anda" → klik **"Konfirmasi"**
4. **Baru setelah itu**, peringatan hilang dari Dashboard staf

Kenapa 2 tahap (bukan staf langsung hilangkan sendiri): supaya atasan
tahu pasti stafnya benar-benar sudah lihat, bukan cuma staf yang bisa
"menghilangkan" peringatan sepihak begitu saja.

## File yang MENIMPA file lama
```
app/Models/UserWarning.php
app/Http/Controllers/Api/V1/UserController.php     (+ acknowledgeWarning,
                                                      + confirmWarning)
app/Http/Resources/UserWarningResource.php          (+ field baru)
routes/api.php                                       (+ 2 route)
app/Livewire/Dashboard.php                           (+ acknowledgeWarning,
                                                        filter confirmed_at)
resources/views/livewire/dashboard.blade.php         (+ tombol "Sudah
                                                        Baca", status)
app/Livewire/Staff/Manage.php                        (+ confirmWarning)
resources/views/livewire/staff/manage.blade.php      (+ status +
                                                        tombol Konfirmasi)
```
File baru: 1 migration, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan view:clear
php artisan test --filter=WarningAcknowledgmentTest
php artisan test
```

## Catatan

Tombol **hapus (✕)** yang sudah ada sebelumnya TETAP ada untuk
atasan — dua cara ini melengkapi satu sama lain: "Konfirmasi" untuk
alur normal (staf sudah lihat, kasus ditutup), "Hapus" untuk koreksi
kalau memang salah catat dari awal.
