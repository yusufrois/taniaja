# Roadmap Tambahan Fase G — Absensi (Setup Notes)

## PENTING — basis file yang dipakai (belajar dari regresi sebelumnya)

Sebelum mulai, saya CEK DULU versi mana yang paling lengkap:
- `routes/api.php` basisnya dari **agro-rbac-a4** (52 route), TAPI saya
  gabungkan manual dengan route Fase C (input stock) yang tidak ada di
  situ — supaya tidak meregresi Fase C
- `RolePermissionSeeder.php` basisnya dari **agro-rbac-a4** (sudah ada
  `cost.view` + `user.warn`)
- `RoleCapabilitySeeder.php` basisnya dari **agro-fase-c** (paling
  lengkap: `plant_loss` + `cost.view` + `user.warn` + `input_item/
  purchase/usage` semua ada)

## File yang MENIMPA file lama
```
app/Models/User.php                          (+ relasi employee())
routes/api.php                               (+ route employee & absensi,
                                               DIGABUNG dengan route Fase C)
database/seeders/RolePermissionSeeder.php    (+ modul employee, attendance,
                                               attendance.view_own)
database/seeders/RoleCapabilitySeeder.php    (assign sesuai skema bisnis)
```
File baru: 2 migration, 2 model, 2 policy, 5 request, 2 resource,
2 controller, 1 factory, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=AttendanceTest
```

## Konsep inti: Employee ≠ User

**Employee** (pegawai) sengaja **terpisah** dari **User** (akun login) —
karena tidak semua pegawai punya akun/HP. `user_id` di Employee itu
OPSIONAL. Kalau nanti Fase H (Gaji) dibangun, gaji akan menempel ke
Employee, bukan ke User — supaya pegawai tanpa akun pun tetap bisa
digaji lewat sistem ini.

## 2 cara absen — beda perilaku, sesuai prinsip "mudah dikoreksi"

**1. Self check-in** (`POST /attendances/check-in`) — untuk pegawai
yang punya akun, absen sendiri:
- **Tidak butuh permission apapun** — siapapun yang akunnya tertaut ke
  data Employee bisa pakai ini
- **GPS wajib** kalau status "Hadir" (bukti lokasi), **tidak wajib**
  kalau "Izin"/"Sakit" (masuk akal, orangnya lagi di rumah)
- **Kalau sudah absen hari itu, panggil lagi otomatis MEMPERBAIKI**
  record yang sama (upsert) — bukan error "sudah pernah absen". Ini
  yang saya maksud "mudah dikoreksi kalau salah input" — pegawai yang
  salah pencet bisa langsung benarkan sendiri tanpa drama.

**2. Diabsenkan orang lain** (`POST /attendances`) — untuk pegawai
tanpa akun:
- Butuh permission `attendance.create`
- **Sengaja TIDAK auto-koreksi** kalau dobel — kalau sudah ada record
  untuk pegawai+tanggal itu, sistem kasih pesan jelas: "sudah ada
  (id: X), pakai PUT untuk koreksi" — supaya tidak ada yang bisa
  diam-diam menimpa punya orang lain tanpa sadar.

## Koreksi kesalahan — `PUT /attendances/{id}`

Ini jalur utama untuk memperbaiki kesalahan (misal: awalnya ditandai
"Alpa" padahal ternyata "Sakit", ada kabar belakangan). Tidak perlu
hapus-lalu-buat-ulang — tinggal update status/catatan di record yang
sama.

## Yang SENGAJA disederhanakan (bukan lupa)

- Supervisor diberi **`attendance.view`** (lihat SEMUA orang di
  company), bukan `attendance.view_team` (cuma timnya) — pola 3-tingkat
  (`view`/`view_team`/`view_own`) dari RBAC Fase A2 **belum** saya
  terapkan penuh ke Absensi, supaya modul ini tidak makin rumit di awal.
  Kalau nanti perlu Supervisor cuma lihat TIMNYA (bukan semua orang),
  kabari, saya tambahkan `attendance.view_team` mengikuti pola yang
  sudah ada.
- Belum ada laporan rekap bulanan absensi — itu nanti bagian dari
  Fase H (Penggajian), karena rekap absensi MEMANG untuk hitung gaji.

## Lanjut ke roadmap

Fase G selesai. Fase H (Penggajian) sekarang bisa mulai — sudah ada
data Absensi untuk dasar hitung gaji Harian & potongan Alpa di gaji
Bulanan.
