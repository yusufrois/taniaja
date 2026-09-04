# Perbaikan Besar: Sidebar Sekarang Benar-Benar Sesuai Izin Peran

## Bug #2 (yang Anda temukan) — akar masalahnya

Hampir SEMUA link sidebar (Data Master, Musim Tanam, seluruh
Keuangan, Laporan) **tidak pernah dicek terhadap izin peran user
yang login** — cuma dicek "apakah modul perusahaan aktif" (untuk
Greenhouse/Musim Tanam) atau tanpa pengecekan sama sekali (untuk
yang lain). Jadi Worker/Finance tetap lihat link-nya, klik, baru
ketahuan 403.

## Solusi

**Setiap** link sidebar sekarang dicek `@can('viewAny', Model::class)`
sesuai izin peran user yang login — persis sama dengan yang menentukan
apakah halamannya bisa dibuka. Kalau user tidak punya izin sama
sekali untuk grup itu (misal SEMUA item Data Master), judul grupnya
juga ikut hilang (bukan cuma isinya kosong).

## Bug #1 (yang Anda tanyakan) — solusi

Staf yang diberi peringatan sekarang **melihat peringatannya sendiri**
di Dashboard mereka — banner kuning muncul otomatis di atas, tanpa
perlu izin khusus (itu data milik mereka sendiri). Peringatan orang
lain tidak pernah bocor ke staf lain.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (perubahan besar, hampir
                                          semua link dapat pengecekan
                                          izin)
app/Livewire/Dashboard.php               (+ ambil peringatan milik
                                          user sendiri)
resources/views/livewire/dashboard.blade.php  (+ banner peringatan)
```
File baru: 2 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=SidebarPermissionTest
php artisan test --filter=DashboardWarningTest
php artisan test
```

## Cara coba manual

1. Login sebagai akun Worker (kalau sudah dibuat lewat Kelola Staf)
   → sidebar seharusnya cuma menampilkan menu yang relevan buat
   Worker (Dashboard doang, kira-kira)
2. Beri 1 peringatan ke akun itu lewat Kelola Staf (pakai akun Owner)
3. Login lagi sebagai Worker itu → banner peringatan kuning muncul
   di Dashboard
