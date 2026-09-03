# Phase 9 UI — Halaman Greenhouse (Pola Referensi Data Master)

Halaman CRUD pertama Phase 9 — dijadikan **pola referensi** untuk
halaman master-data lain (Kebun/Crop, Varietas, Supplier, Customer,
Grade, Kategori Beban) yang akan dibangun menyusul dengan struktur
identik.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (link "Greenhouse" di sidebar
                                          jadi aktif, bukan placeholder
                                          lagi — 2 tempat: desktop &
                                          mobile drawer)
routes/web.php                           (+ route /greenhouses)
```
File baru: 1 komponen Livewire (`Manage.php`), 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan test --filter=GreenhouseManageTest
```
Lalu buka `/greenhouses` di browser (login dulu) untuk lihat hasilnya.

## Catatan: field `capacity`

Saat mengecek `StoreGreenhouseRequest`, saya temukan field `capacity`
divalidasi di backend TAPI **tidak pernah benar-benar tersimpan**
(tidak ada di `$fillable` model `Greenhouse`) — bug lama, tidak
terkait pekerjaan Phase 9 ini. Saya sengaja TIDAK sertakan field itu
di form UI (percuma, toh tidak akan tersimpan). Kalau nanti mau
diperbaiki di backend, itu perbaikan terpisah, bukan bagian UI.

## Pola yang dipakai (untuk halaman master-data berikutnya)

1. **1 komponen Livewire per modul** — list + modal create/edit +
   konfirmasi hapus, semua jadi satu (bukan halaman terpisah per aksi)
2. **Policy yang SAMA dipakai lagi** — tidak reimplementasi aturan
   akses, `$this->authorize()` langsung pakai Policy yang sudah ada
   dari REST API
3. **Validasi identik dengan API** — aturan `unique`, `required`, dst
   disalin persis dari `Store{Model}Request` yang sudah ada
4. Style: `.glass` untuk card/modal, `.lw-input`/`.lw-label` untuk
   form, `.btn-primary`/`.btn-secondary` untuk tombol, `.tab-btn` untuk
   nav — semua sudah didefinisikan di `app.css` sejak awal Phase 9

## Lanjut

Setelah ini teruji jalan, saya lanjut ke halaman master-data lain
(Crop+Variety, Supplier, Customer, Grade, Kategori Beban) dengan pola
yang sama, lalu modul yang lebih kompleks (Musim Tanam, Keuangan,
Laporan, dst).
