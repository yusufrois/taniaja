# Phase 9 UI — 6 Halaman Data Master

Melanjutkan pola dari halaman Greenhouse — 6 halaman baru dengan
struktur identik: Komoditas (Crop), Varietas, Supplier, Customer,
Grade, Kategori Beban.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (+ grup "Data Master" di
                                          sidebar, 2 tempat: desktop
                                          & mobile)
routes/web.php                           (+ 6 route baru)
```
File baru: 6 komponen Livewire, 6 view Blade, 1 test (mencakup semua
6 modul sekaligus).

## Setelah menyalin
```bash
php artisan test --filter=MasterDataManageTest
```
Lalu buka `/crops`, `/varieties`, `/suppliers`, `/customers`,
`/grades`, `/expense-categories` di browser.

## Yang perlu dicoba manual (Varietas)

Halaman **Varietas** punya 1 fitur tambahan: tombol "Varietas" di
baris tiap Komoditas (halaman `/crops`) akan membawa Anda ke
`/varieties?crop_id=X` — daftar varietas otomatis ter-filter cuma
punya komoditas itu. Coba klik tombol itu dari halaman Komoditas
untuk lihat alurnya.

## Pola yang konsisten (sama seperti Greenhouse)

Setiap modul: 1 komponen Livewire (list + modal create/edit + hapus),
Policy yang SAMA dipakai lagi (tidak reimplementasi aturan akses),
validasi identik dengan `Store{Model}Request` yang sudah ada di API.

## Lanjut

6 modul Data Master selesai. Sisa dari sidebar saat ini masih
placeholder: **Musim Tanam, Keuangan, Laporan** — modul-modul yang
jauh lebih kompleks (lintas beberapa entitas, banyak state), jadi
akan dipecah lagi jadi beberapa sesi terpisah nanti.
