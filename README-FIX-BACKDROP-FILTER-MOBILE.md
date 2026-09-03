# Perbaikan FINAL: Bug Rendering Mobile Safari (backdrop-filter)

## Akar masalah sebenarnya — terbukti dari screenshot mode iPhone Anda

Terima kasih sudah kirim screenshot dengan DevTools dalam mode
**emulasi iPhone (375×667)** — itu petunjuk pentingnya. Efek "kaca
buram" (`backdrop-filter: blur()`) yang dipakai class `.glass` di
seluruh aplikasi ini punya **bug yang sudah dikenal di mesin render
WebKit/Safari**: kalau elemen yang pakai efek itu lebih TINGGI dari
layar yang kelihatan (perlu di-scroll), efeknya bisa berhenti
"menggambar" tepat di batas layar — bagian yang di luar area
kelihatan itu jadi TIDAK dapat background yang sama, padahal secara
struktur HTML masih 1 kotak yang sama persis. Makanya kelihatan
seperti "keluar kotak" padahal sebenarnya bukan soal ukuran/posisi.

## Solusi — hilangkan blur-nya khusus untuk modal

Modal sekarang pakai warna latar SOLID biasa (bukan efek kaca buram)
— visualnya nyaris sama (warna gelap kehijauan, border tipis, bayangan),
cuma tanpa `backdrop-filter`. Karena tidak pakai blur sama sekali,
bug render WebKit ini otomatis tidak berlaku lagi, di ukuran layar
apapun.

## File yang MENIMPA file lama

Semua 8 halaman:
```
resources/views/livewire/season/manage.blade.php
resources/views/livewire/greenhouse/manage.blade.php
resources/views/livewire/crop/manage.blade.php
resources/views/livewire/variety/manage.blade.php
resources/views/livewire/supplier/manage.blade.php
resources/views/livewire/customer/manage.blade.php
resources/views/livewire/grade/manage.blade.php
resources/views/livewire/expense-category/manage.blade.php
```

## Setelah menyalin
```bash
php artisan view:clear
```
Coba lagi di HP/emulasi mobile — kali ini seluruh modal dari judul
sampai tombol Simpan seharusnya jadi 1 kotak solid yang utuh, di
layar berapapun tingginya.

## Catatan
Halaman-halaman LAIN di aplikasi ini (kartu daftar, tabel, dst) masih
tetap pakai efek kaca buram `.glass` seperti biasa — itu TIDAK
bermasalah karena tingginya biasanya muat dalam satu layar. Yang saya
ubah HANYA kotak modal (form tambah/edit), karena itu yang paling
sering lebih tinggi dari layar.
