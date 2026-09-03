# Phase 9 UI — Halaman Laporan (PENUTUP SELURUH PHASE 9)

Placeholder terakhir di sidebar — dengan ini, **tidak ada lagi menu
"belum dibuat" tersisa** di seluruh aplikasi.

## Refactor penting: logic laporan dipindah ke Service

Sebelum bangun halaman ini, saya **pindahkan logic Neraca/Laba Rugi/
Neraca Saldo** dari `AccountingReportController` (API) ke class baru
`AccountingReportService` — supaya halaman Laporan ini pakai PERSIS
logic yang sama, bukan salinan kedua yang berisiko bug tanggal yang
sama muncul lagi (logic ini sempat beberapa kali diperbaiki sebelum
akhirnya benar).

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/AccountingReportController.php  (refactor,
                                                               PERILAKU
                                                               API TIDAK
                                                               BERUBAH,
                                                               cuma
                                                               dipindah
                                                               ke Service)
resources/views/layouts/app.blade.php   ("Laporan" jadi link aktif,
                                          2 tempat — TIDAK ADA LAGI
                                          placeholder di sidebar)
routes/web.php                           (+ route /reports)
```
File baru: 1 service (`AccountingReportService`), 1 komponen
Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=ReportManageTest
php artisan test --filter=Accounting
```
(filter `Accounting` sengaja disertakan — untuk pastikan refactor
Controller TIDAK merusak test API yang sudah ada sebelumnya)

## Isi halaman Laporan

3 tab:
- **Laba Rugi** — pilih rentang tanggal, lihat Pendapatan/Beban/Laba Bersih
- **Neraca** — pilih "per tanggal", lihat Aset/Kewajiban/Modal + cek
  keseimbangan
- **Neraca Saldo** — cek total Debit = Kredit di seluruh akun

## Yang SENGAJA belum dibuat

Laporan OPERASIONAL yang lebih spesifik (HPP per musim, traceability
panen→jual, riwayat per petani, performa greenhouse) sudah ADA
API-nya sejak Phase 8/Fase J-K, tapi **belum** punya halaman UI —
kalau dibutuhkan, itu perluasan terpisah dari halaman Laporan ini
(bisa jadi tab tambahan).

## SELESAI: Seluruh Phase 9

Login, Data Master, Musim Tanam, Keuangan (Bagan Akun, Beban, Modal,
Hutang, Aset Tetap), dan sekarang Laporan — semua sudah punya
halaman UI lengkap, tidak ada placeholder tersisa di sidebar manapun.
