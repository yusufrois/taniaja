# Phase 9 (bagian 10) — Logo Pindah ke Topbar + Tombol Transparan

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
1. **Logo dipindah dari sidebar ke topbar**, digabung sebaris dengan
   tanggal/jam (dipisah garis tipis vertikal). Sidebar sekarang isinya
   MURNI ikon menu saja — jadi saat dibuka/tutup, tidak ada elemen logo
   yang ikut menyusut/terpotong lagi, karena memang tidak ada logo di
   situ sama sekali.
2. **Sidebar bisa kembali sempit (64px)** saat tertutup — sebelumnya
   saya lebarkan jadi 140px cuma untuk menampung logo; sekarang logo
   sudah pindah, jadi bisa kembali ke ukuran ikon-saja yang lebih hemat
   tempat.
3. **Tombol toggle sekarang transparan** (kaca tipis dengan border
   samar), bukan hijau solid — menyatu dengan panel gelap di
   sekitarnya, tidak menonjol berlebihan.
