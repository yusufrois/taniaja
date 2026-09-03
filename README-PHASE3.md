# Phase 3 — Musim Tanam / Season (Setup Notes)

## File yang MENIMPA file lama
```
routes/api.php                                  (nambah endpoint season + dashboard)
database/seeders/RoleCapabilitySeeder.php       (generalisasi + permission season)
```
Semua file lain BARU — tinggal disalin ke lokasi yang sama.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
```

## Jalankan test
```bash
php artisan test --filter=SeasonCrudTest
```
Test ini memverifikasi:
- Season berhasil dibuat dengan HST terhitung otomatis dari planting_date
- Variety yang tidak cocok dengan Crop yang dipilih DITOLAK (422)
- Greenhouse tidak boleh punya 2 season yang masih berjalan bersamaan (422)
- Supervisor bisa create season ("propose") tapi TIDAK bisa update — sesuai
  Role & Permission Matrix, beda dari master data yang aksesnya seragam

## Endpoint baru
```
GET/POST            /api/v1/seasons
GET/PUT/DELETE      /api/v1/seasons/{id}
GET                 /api/v1/seasons/{id}/dashboard
```

## Contoh body untuk POST /api/v1/seasons
```json
{
  "greenhouse_id": 1,
  "crop_id": 1,
  "variety_id": 1,
  "season_name": "Musim 2",
  "planting_date": "2026-08-01",
  "plant_count": 1300,
  "target_yield": 1500
}
```
Response akan menyertakan field `hst` (Hari Setelah Tanam) yang dihitung
otomatis — TIDAK perlu dikirim dari client, dan tidak disimpan sebagai
kolom database (dihitung ulang setiap kali diminta, sesuai catatan di
`Season::hst()`).

## Keputusan arsitektur yang diambil di Phase 3 (perlu Anda tahu)
1. **Satu greenhouse = satu season aktif** ditegakkan di level validasi
   (StoreSeasonRequest/UpdateSeasonRequest), bukan constraint database,
   karena "aktif" bergantung pada kolom `status` yang nilainya berubah-ubah
   — unique index tidak bisa menyatakan itu.
2. **HST dihitung live, tidak disimpan.** Kalau disimpan sebagai kolom,
   perlu scheduled job harian untuk update semua season aktif — lebih
   rumit dan rawan telat. Dihitung on-the-fly jauh lebih sederhana dan
   selalu akurat (Aturan #43: jangan overengineering).
3. **RoleCapabilitySeeder digeneralisasi** — sekarang setiap role bisa
   punya kombinasi permission BERBEDA per modul (bukan seragam seperti
   Phase 2), karena Supervisor untuk Season cuma dapat create+view, beda
   dari master data yang view-only. Pola ini dipakai lagi di phase
   berikutnya begitu ada modul dengan aturan akses yang lebih granular
   (mis. Expense: Supervisor bisa input tapi tidak approve).
4. **Dashboard musim (`/seasons/{id}/dashboard`) sudah dibuat sekarang**
   dengan field cost/harvest/sales/activities di-hardcode 0 — supaya
   bentuk response (contract) untuk frontend tidak berubah lagi nanti.
   Nilainya baru terisi nyata setelah Expense (Phase 5), Harvest (Phase 6),
   Sales (Phase 7), dan Activity (Phase 4) selesai dibuat.

## Yang BELUM ada (menyusul Phase 4+)
- Activity Template & auto-generate jadwal dari HST (Phase 4)
- Scheduled vs Ad Hoc Activity (Phase 4)
- Modul yang mengisi angka asli di dashboard musim (Phase 4–7)
