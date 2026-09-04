# PERBAIKAN PENTING: "Peran Cuma Ada Company Owner"

## Akar masalah (ditemukan lewat laporan Anda)

Ternyata **bug nyata**, bukan cara pakai. Proses daftar perusahaan
(`CompanyRegistrationService`) **cuma pernah membuat 1 role: "Owner"**
— Manager/Supervisor/Finance/Worker **tidak pernah dibuat sama
sekali**. Lebih parah lagi: role Owner yang dibuat itu **tidak
langsung dapat izin apapun** — kalau Owner Demo Anda bisa jalan
normal selama ini, itu karena `RoleCapabilitySeeder` pernah dijalankan
manual dan KEBETULAN menemukan role Owner sudah ada lalu mengisi
izinnya — tapi seeder itu sendiri **tidak pernah membuat role yang
belum ada**, cuma mengisi izin ke yang sudah ada. Makanya 4 role
lainnya tetap tidak pernah muncul.

## Solusi

Saya satukan "daftar role apa saja + izin apa saja per role" jadi
**1 sumber kebenaran** (`RoleMatrixService`), dipakai bersama oleh:
1. **Pendaftaran perusahaan baru** — sekarang otomatis membuat
   SEMUA 5 role sekaligus, lengkap dengan izinnya, bukan cuma Owner
2. **`RoleCapabilitySeeder`** — sekarang bisa dipakai untuk
   **memperbaiki perusahaan yang sudah kadung kena bug ini**
   (termasuk perusahaan Anda) — aman dijalankan berkali-kali

## File yang MENIMPA file lama
```
app/Services/Auth/CompanyRegistrationService.php
database/seeders/RoleCapabilitySeeder.php
```
File baru: `app/Services/Auth/RoleMatrixService.php`, 1 test.

## WAJIB dijalankan setelah menyalin

```bash
php artisan db:seed --class=RoleCapabilitySeeder
```

Ini akan **memperbaiki perusahaan Anda yang sudah ada** — membuat
role Manager/Supervisor/Finance/Worker yang selama ini hilang,
lengkap dengan izin masing-masing. Aman dijalankan meski sebagian
data sudah ada (tidak akan dobel/rusak).

```bash
php artisan test --filter=RoleProvisioningTest
php artisan test
```

## Setelah ini

Coba lagi buka **Kelola Staf** → dropdown "Peran" seharusnya sudah
menampilkan Manager, Supervisor, Finance, Worker — tidak cuma Company
Owner lagi.
