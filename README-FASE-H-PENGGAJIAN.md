# Roadmap Tambahan Fase H — Penggajian (Setup Notes)

## PENTING — basis file yang dipakai

Belajar dari regresi-regresi sebelumnya, saya cek dulu versi paling
lengkap sebelum edit:
- `routes/api.php`, `RolePermissionSeeder.php`, `RoleCapabilitySeeder.php`
  semuanya basisnya dari **Fase G** (paling lengkap: sudah ada
  employee/attendance + semua modul sebelumnya)

## File yang MENIMPA file lama
```
app/Models/Employee.php                      (+ pay_type, monthly_salary,
                                                daily_rate, relasi payroll)
routes/api.php                                (+ route penggajian)
database/seeders/RolePermissionSeeder.php    (+ modul work_type,
                                                piece_work_log,
                                                employee_loan, payroll,
                                                payroll.view_own)
database/seeders/RoleCapabilitySeeder.php    (assign sesuai skema bisnis)
```
File baru: 7 migration, 6 model, 5 policy, 5 request, 5 resource,
4 controller, 1 service, 1 factory, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=PayrollTest
```

## Cara pakai — alur lengkap

**1. Atur tipe gaji pegawai** (lewat `PUT /employees/{id}`, field baru):
```json
{ "pay_type": "monthly", "monthly_salary": 3000000 }
```
atau `"pay_type": "daily", "daily_rate": 100000` atau
`"pay_type": "piece_rate"` (tidak perlu rate di Employee — tarifnya
per jenis pekerjaan, lihat langkah 2).

**2. (Kalau ada pegawai borongan) Atur jenis pekerjaan:**
```json
POST /api/v1/work-types
{ "name": "Panen Melon", "unit": "kg", "rate": 5000 }
```

**3. Sepanjang bulan** — Absensi (Fase G) dan/atau catat hasil kerja
borongan:
```json
POST /api/v1/piece-work-logs
{ "employee_id": 5, "work_type_id": 2, "date": "2026-09-15", "quantity": 50 }
```

**4. Akhir bulan — generate payroll (bisa diulang selama masih draft!):**
```
POST /api/v1/payroll-periods/generate
{ "year": 2026, "month": 9 }
```
Kalau ada koreksi absensi SETELAH generate pertama, tinggal panggil
lagi endpoint yang sama — akan **hitung ulang otomatis**, bukan
duplikat.

**5. Kalau sudah yakin benar — finalisasi:**
```
POST /api/v1/payroll-periods/{id}/finalize
```
Ini titik di mana potongan kasbon **beneran** diterapkan ke saldo
pinjaman. Setelah ini, periode terkunci — tidak bisa di-generate ulang.

## Aturan bisnis yang diterapkan (sesuai hasil diskusi)

| Tipe | Rumus |
|---|---|
| Bulanan | `gaji_bulanan - (hari_alpa × (gaji_bulanan ÷ jumlah_hari_kalender_bulan_itu))` — Izin/Sakit TIDAK mengurangi |
| Harian | `jumlah_hari_hadir × tarif_harian` |
| Borongan | jumlah semua `PieceWorkLog.amount` di periode itu |

**Asumsi yang saya buat** (belum eksplisit dikonfirmasi, wajar
didiskusikan lagi kalau tidak sesuai): prorata gaji bulanan pakai
**jumlah hari kalender** di bulan itu (28/29/30/31), BUKAN patokan
tetap seperti "26 hari kerja". Kalau ternyata Anda maunya patokan
tetap, kabari, saya sesuaikan `PayrollCalculationService::calculateMonthly()`.

## Kasbon — kapan benar-benar terpotong

Potongan kasbon **preview** muncul begitu payroll di-generate (draft),
tapi baru **beneran mengurangi saldo pinjaman** saat difinalisasi. Ini
sengaja — supaya generate ulang berkali-kali selama draft tidak
memotong kasbon berkali-kali juga. Kalau pegawai punya lebih dari 1
pinjaman aktif, dipotong dari yang **paling lama** dulu (FIFO).

## Yang SENGAJA belum dibuat

- **PDF slip gaji** — ditunda sengaja, akan dikerjakan bareng
  Invoice/Surat Jalan/Nota (Fase D/E) supaya infrastruktur PDF dibangun
  sekali, dipakai berulang — bukan lupa.
- **Update PieceWorkLog** (cuma ada create+delete) — untuk koreksi
  jumlah, saat ini hapus lalu catat ulang. Kalau perlu endpoint update
  langsung, saya bisa tambahkan.
- **Slip gaji tergabung tampilan sederhana** — endpoint API sudah ada
  semua datanya (`detail` JSON berisi rincian perhitungan), tinggal
  Phase 9 (UI) nanti yang menampilkan dalam bentuk enak dibaca.

## Lanjut ke roadmap

Fase G, H selesai. Berikutnya Fase I (Tugas dari Atasan) atau J/K
(Laporan) — mana saja yang Anda mau duluan.
