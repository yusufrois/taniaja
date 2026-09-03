# Roadmap Tambahan Fase I — Tugas dari Atasan (Setup Notes)

## PENTING — basis file yang dipakai
Semua file basis (`routes/api.php`, `RolePermissionSeeder.php`,
`RoleCapabilitySeeder.php`, `User.php`) diambil dari **Fase H**
(paling lengkap sejauh ini) sebelum diedit — supaya tidak ada regresi
seperti kejadian sebelumnya.

## File yang MENIMPA file lama
```
app/Models/User.php                          (+ relasi task, notification,
                                                device token)
routes/api.php                                (+ route tugas & notifikasi)
database/seeders/RolePermissionSeeder.php    (+ modul task, task.view_own)
database/seeders/RoleCapabilitySeeder.php    (assign sesuai skema)
```
File baru: 4 migration, 4 model, 1 policy, 5 request, 3 resource,
3 controller, 2 service, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=TaskTest
```

## PENTING — soal push notification ke HP

Infrastrukturnya **sudah siap**, tapi supaya benar-benar terkirim ke
HP, perlu:
1. Bikin project Firebase (atau pakai yang sudah ada dari LWC100
   Autodosing kalau mau dipakai bareng)
2. Isi `FCM_SERVER_KEY` di `.env`
3. Frontend (nanti di Phase 9 UI) perlu daftarkan token device lewat
   `POST /api/v1/device-tokens` saat pertama kali user login/buka app

**Tanpa langkah di atas, sistem tetap berjalan normal** — notifikasi
dalam-aplikasi (`GET /notifications`) tetap berfungsi penuh, cuma
push ke HP-nya yang belum aktif (di-skip diam-diam, dicatat ke log).

## 2 Tingkat Kewenangan pada 1 Tugas

Saya bedakan dengan sengaja:
- **Centang checklist / ubah status** — cukup `task.view_own` (Anda
  terlibat di tugas itu, sebagai penerima ATAU pemberi)
- **Ubah judul/deskripsi/due date/tambah langkah baru** — butuh
  `task.update` ATAU Anda yang memberi tugas itu (`assigned_by`)

Supaya bawahan bisa progress tugasnya sendiri tanpa bisa diam-diam
mengganti apa isi tugasnya.

## Endpoint utama
```
POST   /tasks                          — beri tugas baru (bisa sekalian +checklist)
PATCH  /tasks/{id}/status              — ubah status (assignee bisa)
POST   /tasks/{id}/steps               — tambah langkah checklist baru
PATCH  /tasks/{id}/steps/{step}/toggle — centang/batal centang (assignee bisa)
POST   /tasks/{id}/follow-up           — tugas susulan (task BARU, ditandai terkait)
GET    /notifications                  — daftar notifikasi saya
PATCH  /notifications/{id}/read        — tandai sudah dibaca
POST   /device-tokens                  — daftarkan token HP untuk push
```

## Yang SENGAJA belum dibuat
- Update PieceWorkLog-style koreksi jumlah langkah checklist — untuk
  sekarang, ubah/hapus langkah dilakukan lewat re-create, belum ada
  endpoint update teks langkah secara langsung.
- Migrasi ke Firebase HTTP v1 API (yang lebih baru dari legacy server
  key) — dipakai yang lama dulu karena lebih sederhana, bisa
  di-upgrade nanti kalau perlu.

## Lanjut ke roadmap
Fase G, H, I selesai. Berikutnya Fase J/K (Laporan) atau Fase L
(Akuntansi) — mana saja yang Anda mau duluan.
