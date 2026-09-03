# Phase 9 (bagian 6) — Perbaikan Logo & Navbar

## File yang MENIMPA file lama
```
public/images/logo.png                      (background putih dihapus,
                                               jadi transparan sungguhan)
resources/css/app.css                        (tambah .tab-btn, SISA isi
                                               file yang sama seperti
                                               sebelumnya — aman ditimpa)
resources/views/layouts/app.blade.php        (navbar + strip menu tab)
```
`layouts/guest.blade.php` (halaman login) TIDAK perlu ditimpa lagi —
dia otomatis ikut benar begitu `logo.png` diganti, karena filenya
sama-sama menunjuk ke `public/images/logo.png`.

## Setelah menyalin
```bash
npm run build
```
Hard refresh browser (Ctrl+Shift+R).

## Yang diperbaiki

1. **Logo transparan** — background putih di gambar asli saya hapus
   secara terprogram (pixel putih murni jadi transparan, sisanya
   dibiarkan utuh supaya teks dan gradient logo tidak rusak).
2. **Navbar sekarang ada isi** — strip menu tab dengan gaya `.tab-btn`
   yang PERSIS sama classnya dengan CSS Autodosing Anda. "Dashboard"
   otomatis aktif (hijau menyala) kalau sedang di halaman itu.
   4 tab lain (Greenhouse, Musim Tanam, Keuangan, Laporan) sengaja
   ditampilkan **redup/nonaktif** sebagai placeholder — halamannya
   belum dibuat, tapi strukturnya sudah siap begitu Anda mau saya
   lanjutkan bikin halaman-halaman itu.

## Kalau masih kurang pas
Kirim screenshot lagi — sekarang saya sudah pegang token warna asli
Anda jadi koreksinya bisa lebih presisi dari sebelumnya.
