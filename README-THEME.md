# Phase 9 (bagian 5) — Adopsi Tema dari LadangWohIjo Autodosing

## File yang MENIMPA file lama
```
resources/css/app.css                                  (design tokens baru)
resources/views/layouts/guest.blade.php                 (logo + tema gelap)
resources/views/layouts/app.blade.php                   (navbar glass + logo)
resources/views/livewire/auth/login.blade.php
resources/views/livewire/auth/register-company.blade.php
resources/views/livewire/auth/forgot-password.blade.php
resources/views/livewire/auth/reset-password.blade.php
resources/views/livewire/dashboard.blade.php
```

## File BARU
```
public/images/logo.png     (logo TaniAja yang Anda kirim, sudah dikonversi
                             dari .jfif ke PNG asli)
```

## Setelah menyalin
```bash
npm run build
```
Lalu hard refresh browser (Ctrl+Shift+R).

## Apa yang saya adopsi dari CSS Autodosing Anda

Saya baca file `style.css` yang Anda kirim dan ambil persis token warnanya:
- Background: hijau nyaris hitam (`#07110d`) dengan radial glow hijau di
  pojok atas, bukan putih/abu-abu
- Kartu (`.glass`): panel gradient gelap transparan, border hijau tipis,
  backdrop blur — efek "kaca" yang sama persis dengan Autodosing
- Tombol utama (`.btn-primary`): gradient hijau neon (#4ade80→#22c55e),
  sama seperti tombol aksi hijau di Autodosing
- Input form (`.lw-input`): background gelap, border abu-abu tipis, glow
  hijau saat fokus
- Font: **Inter** (sama seperti Autodosing), bukan default browser
- Nama class CSS (`.glass`, `.brand`, dst) SENGAJA saya samakan persis
  dengan nama di CSS Autodosing Anda — supaya kalau nanti mau berbagi
  komponen/styling antara TaniAja dan LWC100 dashboard, tinggal pakai
  nama class yang sama, tidak perlu terjemahkan lagi

## Soal logo

Logo yang Anda kirim (`Gemini_Generated_Image_...jfif`) itu sebenarnya
file JPEG yang diberi ekstensi `.jfif` — saya konversi jadi PNG asli
(`logo.png`) supaya lebih standar dan kompatibel di semua browser.
Dipakai di 2 tempat: halaman login/register (ukuran besar) dan navbar
dashboard (ukuran kecil).

## Kartu dashboard tetap dengan warna beda per kategori

Ide 6-warna dari sebelumnya (biru/merah/hijau/oranye/teal/ungu) saya
pertahankan, tapi sekarang jadi aksen garis kiri tipis (bukan border
tebal) supaya lebih cocok dengan gaya "glass panel" yang minimalis dan
gelap — mengikuti pola visual metric card di Autodosing.

## Kalau masih ada yang mau disesuaikan
Kirim tahu bagian spesifiknya (misal "kartu masih kurang mirip", "logo
kegedean", dll) — saya bisa perbaiki lagi dengan lebih presisi, apalagi
sekarang saya sudah punya referensi CSS asli Anda untuk dicocokkan.
