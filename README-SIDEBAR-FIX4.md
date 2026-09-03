# Phase 9 (bagian 11) — Topbar Full-Width, Sidebar di Bawahnya

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
Struktur halaman sekarang:
```
┌─────────────────────────────────────┐
│         TOPBAR (full width)          │
├───────────┬───────────────────────────┤
│  SIDEBAR  │      KONTEN HALAMAN       │
│  (kiri)   │                           │
└───────────┴───────────────────────────┘
```
Sebelumnya topbar dan sidebar sejajar sebagai 2 kolom (topbar cuma
selebar area konten). Sekarang topbar independen, melebar penuh dari
kiri ke kanan di paling atas, dan sidebar ada di bawahnya — pola
layout dashboard yang lebih umum/familiar.
