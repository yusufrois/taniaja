# Perbaikan: Modal Tidak Bisa Discroll ke Atas

## Penyebab — bug CSS klasik

Modal saya pakai `items-center` (memusatkan modal secara vertikal di
tengah layar) DIGABUNG dengan scroll pada container yang sama. Ini
kombinasi CSS yang punya bug terkenal di banyak browser: kalau isi
modal lebih tinggi dari layar, bagian yang "di atas titik tengah"
bisa jadi TIDAK BISA di-scroll ke atas sama sekali — bukan cuma
soal ukuran layar/zoom, tapi soal bagaimana `align-items: center`
menghitung area yang bisa di-scroll.

Ini persis yang Anda alami: judul "Musim Tanam Baru" dan banner error
di paling atas jadi tidak terjangkau scroll di zoom 100%, cuma
kelihatan setelah zoom-out ke 67%.

## Solusi

Ganti modal supaya **selalu mulai dari atas** (bukan dipusatkan),
dengan jarak dari atas layar yang cukup — jadi seluruh isi modal
(termasuk judul dan banner error) SELALU bisa di-scroll dengan
normal, di zoom level berapapun.

## File yang MENIMPA file lama

Diperbaiki di **SEMUA 8 halaman** (bug yang sama ada di semuanya,
bukan cuma Musim Tanam — Anda benar dugaannya):
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
(Modal konfirmasi hapus TIDAK ikut diubah — ukurannya selalu kecil,
tidak pernah kepotong.)

## Setelah menyalin
```bash
php artisan view:clear
```
Hard refresh browser, coba buka modal Musim Tanam lagi di zoom 100% —
sekarang seharusnya bisa scroll normal dari atas sampai bawah.

## Untuk halaman berikutnya

Pola modal yang benar (`items-start` + `overflow-y-auto py-8`) akan
saya pakai dari awal untuk semua modal baru ke depannya.
