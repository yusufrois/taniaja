# Perbaikan FINAL: Bukan Z-Index, Tapi Transparansi

## Akar masalah sebenarnya — terbukti dari DOM

Terima kasih untuk data DevTools-nya — itu kunci ketemunya. Waktu
Anda klik kanan di tulisan "Musim Tanam" yang bocor, yang ke-select
adalah **elemen modal itu sendiri**, bukan elemen sidebar terpisah.
Artinya modal SUDAH di lapisan paling atas dengan benar — z-index
saya sebelumnya sebenarnya sudah bekerja.

Yang terjadi: latar belakang modal (`bg-black/60`, cuma 60% gelap)
digabung efek kaca (`.glass`, background semi-transparan + blur)
ternyata **cukup tembus pandang** sehingga warna hijau terang dari
tombol sidebar yang aktif ("Musim Tanam") masih kelihatan menembus,
meski POSISINYA sudah benar di belakang modal.

## Solusi

Bikin latar modal jauh lebih pekat:
- Overlay gelap di belakang modal: dari 60% jadi **88% gelap**
- Kotak modal itu sendiri: dari semi-transparan jadi **98% pekat**

Keduanya pakai `style="..."` inline (bukan class Tailwind baru) —
sama seperti perbaikan sebelumnya, supaya pasti langsung berlaku
tanpa perlu `npm run build`.

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
(`resources/views/layouts/app.blade.php` dari perbaikan sebelumnya
TETAP dipakai — itu bagian z-index-nya sudah benar dan tidak perlu
diubah lagi)

## Setelah menyalin
```bash
php artisan view:clear
```
Hard refresh browser, coba buka modal Musim Tanam lagi — kali ini
latar belakangnya seharusnya pekat, tidak ada lagi warna/teks yang
tembus dari belakang.
