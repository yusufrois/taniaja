# Halaman Baru: Kelola Staf

Akhirnya bisa buat akun Finance/Supervisor/Worker lewat web — sebelum
ini cuma bisa lewat API/Postman langsung.

## Isi halaman

- **Daftar staf**: nama, email, peran, atasan, status
- **Tambah/Edit staf**: nama, email, password, pilih peran, pilih
  atasan (opsional, untuk hierarki tim)
- **Nonaktifkan/Aktifkan**: blokir login staf tanpa hapus datanya
- **Peringatan**: catat pelanggaran, lihat riwayat peringatan
  sebelumnya (perlu izin `user.warn` terpisah dari `user.update`)

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (+ "Kelola Staf", 2 tempat)
routes/web.php                           (+ route /staff)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=StaffManageTest
```

## Cara pakai untuk coba peran lain

1. Buka **Kelola Staf** → **+ Tambah Staf**
2. Isi nama/email/password, pilih **Peran: Finance** (atau
   Supervisor/Worker)
3. Simpan, lalu logout dari akun Owner
4. Login pakai email/password staf baru itu — sidebar akan
   otomatis menyesuaikan sesuai izin peran tersebut
