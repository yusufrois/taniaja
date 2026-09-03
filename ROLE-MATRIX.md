# Matrix Role TaniAja — Referensi Lengkap

Dokumen ini untuk referensi Anda sendiri atau onboarding staf baru —
ringkasan apa yang BISA dan TIDAK BISA dilakukan tiap role, hasil
audit menyeluruh Fase F terhadap seluruh sistem yang sudah dibangun.

## Ringkasan per Role

### 👑 Owner
Akses PENUH ke semuanya, termasuk yang sensitif:
- Kelola staf (tambah/hapus/suspend/peringatan)
- Profil perusahaan (nama, alamat, kontak)
- **Audit trail** — riwayat siapa mengubah apa, kapan
- Akuntansi penuh (Bagan Akun, Jurnal, Neraca, dst)
- Semua modul operasional (kebun, panen, jual-beli, dst)

### 👔 Manager
Sama seperti Owner, KECUALI:
- ❌ Tidak bisa kelola akun staf lain
- ❌ Tidak bisa lihat audit trail
- ❌ Tidak bisa ubah profil perusahaan
- Modal & Aset Tetap: lihat saja, tidak bisa tambah/ubah (itu urusan Owner/Finance)

### 🌱 Supervisor
Fokus operasional lapangan:
- Kelola tim: absensi, tugas, catat hasil kerja borongan
- Catat panen, kematian tanaman, pemakaian pupuk
- Urus Surat Jalan (pengiriman fisik)
- ❌ **Tidak pernah lihat harga/biaya/margin** (`cost.view`)
- ❌ Tidak akses akuntansi, gaji, atau kelola akun staf

### 💰 Finance
Fokus keuangan & administrasi:
- Jalankan penggajian, kelola kasbon karyawan
- Kelola modal, aset tetap, hutang-piutang
- Akuntansi penuh (Bagan Akun, Jurnal, laporan keuangan)
- Lihat harga/biaya/margin di semua modul
- ❌ Tidak catat panen/aktivitas lapangan langsung
- ❌ Tidak kelola akun staf atau data master kebun/greenhouse

### 🧑‍🌾 Worker
Paling terbatas, cuma yang relevan untuk kerja hariannya:
- Catat aktivitas kerja, panen, laporan tanaman mati
- Absen sendiri, lihat slip gaji sendiri, lihat tugas sendiri
- ❌ **Tidak pernah lihat harga/biaya/margin**
- ❌ Tidak akses data staf lain, akuntansi, atau modul admin apapun

## Prinsip Keamanan yang Ditegakkan

1. **User tanpa role apapun = tidak bisa apa-apa** (fail closed, bukan
   fail open) — sudah diuji otomatis.
2. **`cost.view`** (harga/biaya/margin) SELALU dipisah dari akses
   modul itu sendiri — Supervisor & Worker bisa CATAT panen/aktivitas,
   tapi TIDAK PERNAH lihat angka rupiahnya (Fase B, sejak awal).
3. **Audit trail & profil perusahaan** cuma Owner — bahkan Manager
   tidak bisa akses, karena ini menyangkut jejak SEMUA orang termasuk
   Manager sendiri.

## 2 Fitur yang "Kelupaan" — Ditemukan & Dibangun di Fase F

Sambil audit, saya temukan 2 permission (`company.*`, `audit.*`) yang
sudah ada di katalog sejak awal proyek tapi **tidak pernah benar-benar
terhubung ke fitur apapun** — tidak ada controller-nya sama sekali.
Data audit trail sudah terus terkumpul sejak Phase 1 (lewat
`LogsAudit` yang dipakai hampir semua controller), tapi sebelumnya
TIDAK ADA CARA membacanya. Sekarang sudah ada:
```
GET   /company              — lihat profil perusahaan
PATCH /company               — update profil (nama, alamat, dst)
GET   /audit-logs            — lihat jejak audit (filter: model, action, user_id)
```

## File yang MENIMPA file lama
```
database/seeders/RoleCapabilitySeeder.php   (+ company, audit untuk Owner)
routes/api.php                               (+ 3 route baru)
```
File baru: 2 controller, 2 request/resource pair, 2 policy, 3 test
file (termasuk `RoleMatrixSanityTest` — tes kewarasan seluruh matrix
role sekaligus).

## Setelah menyalin
```bash
php artisan test --filter=Company
php artisan test --filter=Audit
php artisan test --filter=RoleMatrixSanityTest
```

## Yang SENGAJA belum dibuat
- **Endpoint suspend company** (matikan seluruh akun 1 perusahaan) —
  sempat disebut di roadmap awal sebagai fitur terpisah untuk Super
  Admin, bukan bagian dari role biasa. Bisa dibangun terpisah kalau
  dibutuhkan.
- **UI role picker/permission editor** — dokumen ini (ROLE-MATRIX.md)
  cukup untuk referensi manual sekarang; kalau nanti mau ada
  antarmuka visual untuk atur permission per role (bukan cuma lewat
  seeder), itu pekerjaan Phase 9 (UI).
