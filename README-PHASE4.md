# Phase 4 — Budidaya (Setup Notes)

## File yang MENIMPA file lama
```
routes/api.php                                  (nambah endpoint Phase 4)
database/seeders/RoleCapabilitySeeder.php       (tambah modul template & activity)
app/Models/Season.php                           (tambah relasi schedules() & activities())
app/Http/Controllers/Api/V1/SeasonController.php (dashboard sekarang isi angka aktivitas asli)
```
Semua file lain BARU.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=ScheduleGenerationTest
```

## Alur kerja Phase 4 (paling penting dipahami)

1. **Buat Activity Template** untuk sebuah Variety:
   `POST /api/v1/activity-templates` — body: `{ "variety_id": 1, "name": "Template Honey Globe Standar" }`
2. **Tambah item template** (bisa berkali-kali, satu per aktivitas):
   `POST /api/v1/activity-templates/{id}/items` — body:
   `{ "hst": 7, "activity_name": "Pemupukan", "category": "pemupukan" }`
3. **Generate jadwal** untuk sebuah Season yang variety-nya sudah punya template:
   `POST /api/v1/seasons/{id}/generate-schedule`
   → otomatis membuat baris `schedules` dengan `scheduled_date = planting_date + hst`
4. **Jalankan jadwal** (Scheduled Activity):
   `POST /api/v1/schedules/{id}/complete` — body opsional `{ "cost": 50000, "notes": "..." }`
   → otomatis membuat `Activity` record DAN menandai schedule `completed`
5. **Skip jadwal** kalau tidak jadi dikerjakan:
   `POST /api/v1/schedules/{id}/skip`
6. **Catat Ad Hoc Activity** (di luar jadwal):
   `POST /api/v1/activities` — body: `{ "season_id": 1, "date": "2026-08-24", "category": "hama", "description": "Penyemprotan thrips", "cost": 350000 }`

## Keputusan arsitektur penting (perlu Anda tahu)

1. **Overdue dihitung live**, sama seperti HST — bukan disimpan di kolom `status`.
   `schedules.status` di database hanya pernah berisi pending/in_progress/
   completed/skipped; API melaporkan "overdue" secara dinamis lewat
   `Schedule::effectiveStatus()` kalau `pending` + tanggalnya sudah lewat.
2. **Permission `activity` dipisah dari permission `season`.** Worker boleh
   menjalankan/mencatat aktivitas (izin `activity.*`) TANPA butuh akses ke
   Season itu sendiri (`season.*`) — ini sesuai Role & Permission Matrix
   (Worker: "Jalankan Scheduled Activity" ✅, "Buat/Edit Season" ❌). Awalnya
   saya sempat salah menggabungkan keduanya saat menulis kode; sudah
   diperbaiki sebelum paket ini dikirim.
3. **Snapshot, bukan referensi live.** Saat schedule dibuat dari template
   item, field `activity_name`/`category`/`instruction` DI-COPY ke tabel
   `schedules`, bukan cuma menyimpan `activity_template_item_id` lalu
   join setiap kali. Jadi kalau template item-nya diedit atau dihapus
   nanti, jadwal yang sudah terbentuk untuk season yang sedang berjalan
   tidak berubah retroaktif — sesuai perilaku dunia nyata (SOP yang
   direvisi tidak mengubah riwayat kerja yang sudah dijadwalkan).
4. **Generate schedule bersifat manual/eksplisit**, bukan otomatis saat
   Season dibuat — supaya pengguna bisa menyesuaikan tanggal/data season
   dulu sebelum jadwal dibuat, dan aman di-klik ulang (idempotent: kalau
   sudah ada schedule untuk season itu, tidak akan dibuat dobel).
5. **`schedules` TIDAK pakai soft delete** (beda dari kebanyakan tabel
   lain) — jadwal adalah artefak perencanaan yang bisa dibuang bersih,
   bukan transaksi finansial/audit-sensitif yang wajib soft delete
   (Aturan #30 hanya mewajibkan itu untuk transaksi finansial penting).

## Endpoint baru
```
GET/POST            /api/v1/activity-templates
GET/PUT/DELETE      /api/v1/activity-templates/{id}
POST                /api/v1/activity-templates/{id}/items
DELETE              /api/v1/activity-templates/{id}/items/{item_id}

GET                 /api/v1/seasons/{id}/schedules
POST                /api/v1/seasons/{id}/generate-schedule
POST                /api/v1/schedules/{id}/complete
POST                /api/v1/schedules/{id}/skip

GET                 /api/v1/seasons/{id}/activities
POST                /api/v1/activities          (ad hoc)
GET/DELETE          /api/v1/activities/{id}
```

## Yang BELUM ada (menyusul Phase 5+)
- Modal, Asset (termasuk mengisi `Greenhouse::constructionCost()` yang
  masih 0 sejak Phase 1), Expense, Debt (Phase 5)
- `total_cost` di dashboard musim masih 0 sampai Expense selesai
- Upload foto aktivitas (Section 28) sengaja BELUM dibuat di Phase 4 ini
  untuk menjaga scope tetap sederhana (Aturan #43) — akan ditambahkan
  saat modul storage/file upload dikerjakan bersamaan dengan modul lain
  yang butuh lampiran (nota pembelian, bukti pembayaran, dst di Phase 5-7),
  supaya pola upload-nya konsisten dibuat sekali untuk semua kebutuhan.
