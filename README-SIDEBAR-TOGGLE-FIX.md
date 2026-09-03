# Phase 9 (bagian 8) — Perbaikan Posisi Tombol Buka/Tutup Sidebar

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
Tombol buka/tutup sidebar sekarang jadi **tombol panah bulat kecil**
yang nempel di pinggir KANAN sidebar, sejajar vertikal di tengah —
bukan tombol lebar di bagian bawah lagi. Ini pola yang sama seperti
kebanyakan aplikasi dashboard modern, lebih gampang dijangkau karena
posisinya konsisten di tepi, tidak perlu scroll ke bawah dulu.
