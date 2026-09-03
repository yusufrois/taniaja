# Perbaikan: Notifikasi Error Validasi di Semua Modal

## Masalahnya

Validasi di belakang layar SUDAH BENAR (terbukti dari semua test yang
lolos) — tapi kalau ada input salah/kurang, pengguna **tidak diberi
tahu dengan jelas** apa yang salah. Sebelumnya cuma ada teks kecil
merah di bawah tiap field, gampang terlewat — terutama untuk aturan
lintas-field seperti "1 Greenhouse cuma boleh 1 musim berjalan" yang
errornya tidak selalu jelas terlihat di dekat field yang mana.

## Solusi

Tambah **banner error jelas di bagian atas form** setiap kali ada
error validasi — daftar SEMUA pesan error sekaligus, tidak perlu
scroll cari-cari.

## File yang MENIMPA file lama

Diperbaiki di **SEMUA 8 halaman** (bukan cuma Musim Tanam) supaya
konsisten:
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
Lalu hard refresh browser, coba lagi buat Musim Tanam dengan data
kurang lengkap atau Greenhouse yang sudah ada musim berjalan — kali
ini akan muncul kotak merah jelas di atas form berisi semua yang
perlu diperbaiki.

## Catatan untuk halaman berikutnya

Pola banner error ini akan saya sertakan dari awal untuk semua modal
berikutnya (Keuangan, Laporan, dst) — supaya tidak ada gap serupa
lagi ke depannya.
