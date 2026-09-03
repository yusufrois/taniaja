# Phase 9 (bagian 9) — Logo Tetap Utuh + Tombol Bentuk Pil

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php
```

## Setelah menyalin
```bash
npm run build
```
Hard refresh browser.

## Yang berubah
1. **Logo sekarang selalu ukuran & posisi sama**, tidak terpengaruh
   buka/tutup sidebar sama sekali. Lebar sidebar versi "tertutup"
   saya lebarkan dari 68px jadi 140px — cukup luas supaya logo penuh
   selalu muat tanpa terpotong/menyusut. Yang hilang cuma teks label
   menu (Dashboard, Greenhouse, dst), ikonnya tetap kelihatan.
2. **Tombol buka/tutup jadi bentuk pil tipis memanjang vertikal**
   (bukan bulat lagi), masih di posisi yang sama (nempel pinggir kanan
   sidebar, sejajar tengah).
