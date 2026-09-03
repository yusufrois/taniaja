# Halaman Baru: Detail Musim Tanam (Laba Rugi + Rincian Pengeluaran per Musim)

Prioritas dinaikkan berdasarkan diskusi soal kebutuhan evaluasi
GH-1 Musim 1 yang sudah selesai.

## Isi halaman

- **Kartu ringkasan**: Pendapatan, Biaya Produksi, Laba/Rugi Bersih,
  HPP/kg — persis angka yang sama dengan API (dipastikan lewat
  Service bersama, bukan hitungan kedua)
- **Nilai stok belum terjual** (kalau ada)
- **Rincian Pengeluaran**: daftar SEMUA beban musim itu, lengkap
  dengan status "✓ Disetujui" / "Menunggu" — supaya transparan kenapa
  angka Biaya Produksi mungkin lebih kecil dari yang Anda kira (yang
  masih "Menunggu" TIDAK ikut dihitung, sesuai aturan approval Beban)

Akses dari tombol **"Detail"** (biru) di setiap baris tabel Musim
Tanam.

## Bonus: gap konsistensi ditemukan & diperbaiki

Ternyata `seasonProfitLoss`/`seasonHpp` di API **belum ikut** aturan
"Beban Menunggu tidak masuk laporan" (fix #1 dulu cuma menyentuh
Dashboard & Laba Rugi Perusahaan) — sudah diperbaiki sekalian supaya
konsisten di semua laporan.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/ReportController.php   (refactor +
                                                     fix konsistensi
                                                     approved_at,
                                                     PERILAKU API
                                                     lain TIDAK berubah)
routes/web.php                                       (+ route detail)
resources/views/livewire/season/manage.blade.php     (+ tombol Detail)
```
File baru: `app/Services/SeasonReportService.php`,
`app/Livewire/Season/Detail.php`,
`resources/views/livewire/season/detail.blade.php`, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=SeasonDetailTest
php artisan test --filter=ReportsTest
php artisan test
```
(filter `ReportsTest` sengaja disertakan — pastikan refactor
`ReportController` tidak merusak test API yang sudah ada)

## Belum termasuk di halaman ini

Riwayat Panen dan Tanaman Mati per musim (item roadmap "Detail Musim
Tanam" yang lebih luas) — sengaja difokuskan ke sisi finansial dulu
sesuai kebutuhan yang dibahas. Bisa ditambahkan sebagai section baru
kalau dibutuhkan.
