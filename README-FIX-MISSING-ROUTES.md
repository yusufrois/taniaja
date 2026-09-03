# Perbaikan: Route "web.reports" Hilang (dan Verifikasi Menyeluruh)

## Penyebab

Beberapa paket saya sepanjang sesi ini bercabang dari salinan
`routes/web.php` yang **lebih lama** (dari sebelum Laporan
ditambahkan) — jadi `web.reports` diam-diam tidak pernah ikut
terbawa, meski `app.blade.php` (layout/sidebar) selalu ter-update
dengan benar. Ini murni salah urutan saya menyalin file dasar antar
paket, bukan salah Anda menerapkan sesuatu.

## Perbaikan kali ini

File ini saya **verifikasi ulang dari nol** — dicek silang terhadap
SEMUA `route('web.xxx')` yang pernah dipakai di layout manapun
sepanjang sesi ini (bukan cuma disalin dari paket terakhir), supaya
tidak ada lagi yang kelewat. Total 16 route web, semuanya terkonfirmasi
ada:

greenhouses, crops, varieties, suppliers, customers, grades,
expense-categories, asset-categories, seasons, seasons.detail,
expenses, chart-of-accounts, capital, debts, assets, reports.

## File yang MENIMPA file lama
```
routes/web.php
```

## Setelah menyalin
```bash
php artisan route:clear
php artisan route:list --name=web.
```
Perintah kedua akan menampilkan semua 16 route `web.*` — pastikan
`web.reports` ada di daftarnya, lalu coba buka halaman Laporan lagi.

## Kalau nanti nemu "Route [web.xxx] not defined" lagi

Itu tandanya ada file `routes/web.php` dari paket lama yang kepakai
lagi entah bagaimana — kirim saja pesan errornya, saya cek ulang
seperti ini.
