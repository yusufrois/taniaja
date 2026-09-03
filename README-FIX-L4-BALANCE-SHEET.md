# Perbaikan Final: Neraca & Laba Rugi — Query Diganti Total

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/AccountingReportController.php
```
Test-nya SAMA seperti paket L4 asli (tidak perlu ditimpa lagi kalau
sudah pernah disalin — tapi saya ikut sertakan untuk memastikan
tidak ada sisa `dump()` debug yang ketinggalan).

## Apa yang berubah

Setelah proses debug bertahap (log di controller membuktikan akun
Modal Pemilik dihitung 0 padahal seharusnya 10 juta), saya **ganti
total cara query tanggalnya** — dari `whereHas('journalEntry', ...)`
jadi 2 langkah eksplisit: ambil dulu daftar ID jurnal yang cocok
tanggalnya, baru jumlahkan baris yang `journal_entry_id`-nya ada di
daftar itu. Lebih panjang kodenya, tapi jauh lebih gampang dipastikan
benar — dan sudah terbukti dari data log kemarin bahwa pendekatan
lama (`whereHas`+closure bersarang) menghasilkan angka 0 yang salah
untuk perbandingan tanggal `<=`, meski pola yang sama persis berhasil
untuk perbandingan `whereBetween` di endpoint Laba Rugi.

**Saya belum 100% pastikan akar mekanisme pastinya kenapa dua pola
yang terlihat identik berperilaku beda** — tapi pendekatan baru ini
tidak bergantung pada mekanisme yang meragukan itu sama sekali, jadi
amannya terjamin oleh strukturnya sendiri, bukan oleh dugaan saya.

## Setelah menyalin
```bash
php artisan test --filter=Accounting
```
