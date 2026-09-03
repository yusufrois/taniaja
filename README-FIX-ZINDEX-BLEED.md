# Perbaikan: Sidebar Bocor Menembus Modal (Z-Index)

## Penyebab

Header dan sidebar tidak punya `z-index` eksplisit, sementara modal
pakai `z-50`. Di sebagian kondisi (kombinasi CSS `backdrop-filter`
yang dipakai `.glass` dengan struktur flexbox layout), urutan lapisan
(stacking) bisa jadi tidak terduga — sidebar/header muncul menembus
modal meski seharusnya modal di lapisan paling atas.

## Solusi

- Header & sidebar: dikunci di lapisan RENDAH (`z-10`) secara eksplisit
- Modal (create/edit DAN konfirmasi hapus): dinaikkan ke lapisan lebih
  tinggi (`z-[100]`) secara eksplisit

Dengan angka yang jelas beda jauh, urutan lapisan tidak lagi
bergantung pada perilaku implisit browser yang bisa tidak konsisten.

## File yang MENIMPA file lama

```
resources/views/layouts/app.blade.php                 (header + sidebar
                                                         dapat z-10)
resources/views/livewire/season/manage.blade.php
resources/views/livewire/greenhouse/manage.blade.php
resources/views/livewire/crop/manage.blade.php
resources/views/livewire/variety/manage.blade.php
resources/views/livewire/supplier/manage.blade.php
resources/views/livewire/customer/manage.blade.php
resources/views/livewire/grade/manage.blade.php
resources/views/livewire/expense-category/manage.blade.php
```
(Semua 8 halaman + layout diperbaiki sekaligus — pola yang sama
dipakai di semuanya.)

## Setelah menyalin
```bash
php artisan view:clear
```
Hard refresh browser, coba buka modal Musim Tanam lagi — sidebar
seharusnya tidak lagi terlihat menembus modal.

## Untuk halaman berikutnya

Pola z-index ini (`z-10` untuk header/sidebar, `z-[100]` untuk modal)
akan saya pakai konsisten mulai dari awal untuk semua halaman baru.
