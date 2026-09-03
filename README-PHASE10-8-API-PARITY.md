# Phase 10 (Bagian 2): Bug #8 Sekarang Berlaku di API Juga (Flutter-Ready)

## Kenapa ini penting

Perbaikan bug #8 sebelumnya (status Musim Tanam salah jadi "Panen")
cuma saya taruh di halaman web (Livewire) — kalau Flutter nanti
memanggil API langsung, bug itu **masih akan terjadi** di sana, tidak
ikut terperbaiki. Sesuai tujuan Phase 10 (API/Flutter readiness),
logic ini sekarang dipindah ke **satu Service class** yang dipakai
BERSAMA oleh API dan web — supaya keduanya selalu punya perilaku
yang identik, tidak ada lagi "kebetulan cuma benar di satu tempat".

## Yang diperbaiki

1. **`SeasonStatusService`** (baru) — logic "actual_harvest_date di
   masa depan tidak boleh langsung jadi status Panen" dan "otomatis
   Aktif begitu tanggal tanam tiba", sekarang di SATU tempat
2. **`SeasonController`** (API) — sekarang pakai Service ini di
   `store()`, `update()`, DAN `index()` (untuk promosi status)
3. **Ditemukan gap tambahan**: `StoreSeasonRequest` bahkan **tidak
   pernah memvalidasi** `actual_harvest_date` sama sekali — jadi kalau
   Flutter kirim field itu saat membuat musim baru, akan diam-diam
   diabaikan. Sudah ditambahkan.
4. **Validasi tanggal panen ≥ tanggal tanam** sekarang juga berlaku di
   API (`StoreSeasonRequest` & `UpdateSeasonRequest`), bukan cuma web
5. Livewire web juga saya update supaya sama-sama manggil
   `SeasonStatusService` (bukan simpan 2 salinan logic terpisah)

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/SeasonController.php
app/Http/Requests/StoreSeasonRequest.php
app/Http/Requests/UpdateSeasonRequest.php
app/Livewire/Season/Manage.php
```
File baru: `app/Services/SeasonStatusService.php`, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=SeasonApiParityTest
php artisan test --filter=SeasonHarvestDateBugTest
php artisan test --filter=SeasonManageTest
php artisan test
```

## Catatan desain penting untuk update parsial (PATCH)

Kalau Flutter nanti update SEBAGIAN field saja (misal cuma kirim
`actual_harvest_date` tanpa `planting_date`), validasi urutan tanggal
tetap benar — dicek manual pakai tanggal tanam yang SUDAH TERSIMPAN di
database, bukan mengandalkan aturan bawaan yang bisa salah kalau
field pembandingnya tidak ikut dikirim.

## Lanjut

Masih ada **#5 (Luas GH otomatis)**, **#6 (Aset Tetap belum posting
jurnal)**, **#9 (proteksi hapus data yang masih dipakai)** — akan saya
bangun di level API terlebih dulu, sesuai prinsip Phase 10.
