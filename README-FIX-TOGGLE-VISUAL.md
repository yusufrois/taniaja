# Perbaikan: Toggle Switch Tidak Terlihat di Pengaturan Modul

## Penyebab (kemungkinan besar)

Sama seperti kasus `z-[100]` sebelumnya: toggle switch-nya pakai
class Tailwind (`w-11`, `h-6`, `rounded-full`, dll) yang **belum
pernah dipakai di aplikasi ini** — perlu `npm run build` (bukan cuma
`view:clear`) supaya class itu benar-benar masuk ke CSS. Karena
belum ke-compile, tombolnya jadi tidak terlihat sama sekali.

## Solusi

Semua styling toggle switch (ukuran, bentuk bulat, warna, animasi)
sekarang pakai `style="..."` langsung — CSS mentah yang PASTI
langsung berlaku di browser, tidak pernah butuh proses build apapun.

## File yang MENIMPA file lama
```
resources/views/livewire/settings/module-toggle.blade.php
```

## Setelah menyalin
```bash
php artisan view:clear
```
(tidak perlu `npm run build`)

Hard refresh browser, buka lagi halaman Pengaturan Modul — toggle
switch-nya seharusnya sekarang terlihat (hijau kalau aktif, abu-abu
kalau nonaktif), bisa diklik untuk geser on/off.
