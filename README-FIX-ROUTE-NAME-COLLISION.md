# Perbaikan: Nama Route Web Bentrok dengan API

## Akar masalah — murni salah saya

`Route::apiResource('greenhouses', ...)` di `routes/api.php` **otomatis**
membuat nama route `greenhouses.index` (mengarah ke `/api/v1/greenhouses`).
Saya pakai nama YANG SAMA PERSIS untuk route halaman web
(`/greenhouses`) — jadi Laravel bingung mau pakai yang mana, dan
ternyata memilih yang API. Ini kejadian di SEMUA 7 route web yang
saya buat (Greenhouse + 6 Data Master), karena semuanya memang sudah
punya REST API resource dengan pola nama yang sama.

Terbukti dari HTML yang Anda kirim: `href="http://.../api/v1/greenhouses"`
— seharusnya `href="http://.../greenhouses"`.

## File yang MENIMPA file lama
```
routes/web.php                                       (semua nama route
                                                        diganti prefix
                                                        'web.')
resources/views/layouts/app.blade.php                 (semua referensi
                                                        route() diperbaiki,
                                                        2 tempat: desktop
                                                        & mobile)
resources/views/livewire/crop/manage.blade.php        (link tombol
                                                        "Varietas" ikut
                                                        diperbaiki)
```

## Setelah menyalin
```bash
php artisan route:clear
php artisan view:clear
```
Lalu hard refresh browser (Ctrl+Shift+R) dan coba klik menu Greenhouse
lagi.

## Pelajaran untuk halaman web berikutnya

Setiap kali bikin route WEB baru untuk modul yang SUDAH punya REST API
(hampir semua modul di proyek ini), nama route-nya HARUS dibedakan
dari nama otomatis `Route::apiResource()` — pola yang saya pakai
sekarang (`web.` prefix) akan saya pakai konsisten untuk semua
halaman web berikutnya (Musim Tanam, Keuangan, Laporan, dst).
