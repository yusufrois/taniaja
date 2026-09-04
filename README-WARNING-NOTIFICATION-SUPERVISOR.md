# Perbaikan 3 Temuan dari Screenshot Anda

## 1. Bel Notifikasi & Peringatan disatukan

Sebelumnya 2 sistem terpisah total. Sekarang:
- Saat Peringatan diterbitkan → otomatis jadi Notifikasi juga
  (muncul di bel 🔔, bukan cuma banner Dashboard)
- Saat staf klik "Sudah Baca" → atasan (yang menerbitkan) dapat
  Notifikasi juga — tidak perlu cek manual ke Kelola Staf lagi

## 2. (Otomatis teratasi oleh #1)

## 3. Semua atasan yang punya anak buah bisa kirim peringatan

- Izin sekarang: role dengan `user.warn` penuh (Owner dkk) **ATAU**
  siapa pun yang jadi **atasan langsung** staf itu (dicek dari field
  Atasan) — supervisor tanpa role khusus pun bisa peringatkan anak
  buahnya sendiri
- **Akun baru TETAP cuma Owner** yang bisa buat — tidak berubah
- Konsekuensi: atasan yang sebelumnya TIDAK BISA buka halaman Kelola
  Staf sama sekali (karena tidak punya izin `user.view`) sekarang
  BISA buka, tapi **daftar stafnya dibatasi ke tim mereka sendiri
  saja** — bukan seluruh perusahaan seperti yang Owner lihat
- Tombol "+ Tambah Staf" tetap tersembunyi buat mereka

## File yang MENIMPA file lama
```
app/Models/User.php                                  (+ isSupervisorOf)
app/Http/Controllers/Api/V1/UserController.php        (+ Notifikasi
                                                         otomatis, izin
                                                         atasan-langsung)
app/Http/Requests/StoreUserWarningRequest.php         (izin atasan-langsung)
app/Livewire/Dashboard.php                            (+ Notifikasi ke
                                                         atasan)
app/Livewire/Staff/Manage.php                         (akses halaman +
                                                         daftar staf
                                                         terbatas + izin
                                                         atasan-langsung)
resources/views/livewire/staff/manage.blade.php       (tombol Peringatan
                                                         per-baris)
resources/views/layouts/app.blade.php                 (link sidebar
                                                         Kelola Staf)
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=SupervisorWarningTest
php artisan test
```

## Cara coba manual

1. Buat 1 akun Supervisor (lewat Kelola Staf, sebagai Owner), lalu
   tambah 1 Worker dengan **Atasan = Supervisor tadi**
2. Login sebagai Supervisor itu → menu **Kelola Staf** sekarang
   muncul (sebelumnya tidak), tapi cuma tampil 1 Worker itu (anak
   buahnya)
3. Klik "Peringatan" → berhasil kirim, meski Supervisor TIDAK punya
   izin `user.warn` blanket
4. Login sebagai Worker itu → cek bel 🔔, peringatannya muncul di
   situ juga, bukan cuma di Dashboard
