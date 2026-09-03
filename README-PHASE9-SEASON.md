# Phase 9 UI — Halaman Musim Tanam (Season)

Modul kedua di sidebar, lebih kompleks dari Data Master: dropdown
Komoditas → Varietas yang saling terkait, plus 2 aturan bisnis yang
sama seperti di API.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   ("Musim Tanam" jadi link aktif,
                                          2 tempat)
routes/web.php                           (+ route /seasons, nama
                                          'web.seasons' — ikut pola
                                          prefix 'web.' dari perbaikan
                                          kemarin)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan route:clear
php artisan test --filter=SeasonManageTest
```

## 2 aturan bisnis yang direplikasi dari API (bukan cuma tampilan)

1. **Varietas harus milik Komoditas yang dipilih** — kalau pilih
   Melon lalu pilih varietas Tomat, ditolak.
2. **1 Greenhouse cuma boleh punya 1 musim yang masih berjalan**
   (status planning/active/harvesting) — sama seperti
   `StoreSeasonRequest`/`UpdateSeasonRequest` di API.

Keduanya diuji di test, bukan cuma disalin tanpa verifikasi.

## Yang ditampilkan di tabel (dihitung live, bukan disimpan)

- **HST** (Hari Setelah Tanam) — berhenti terhitung otomatis begitu
  status jadi "Selesai"/"Dibatalkan"
- **Kelangsungan Hidup** (survival rate %) — dihitung dari jumlah
  tanaman dikurangi laporan tanaman mati

## Yang SENGAJA belum dibuat di halaman ini

Halaman ini murni CRUD data musim tanam. Hal-hal yang TERKAIT musim
(jadwal aktivitas, catat panen, laporan tanaman mati) **belum**
diintegrasikan ke sini — itu perluasan terpisah kalau dibutuhkan
(mis. tombol "Lihat Detail" yang membuka halaman aktivitas/panen per
musim).

## Lanjut

Sisa placeholder sidebar: **Keuangan, Laporan**.
