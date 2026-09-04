# Halaman Baru: Panen (Fase UI-1, Item #2)

## Isi halaman

- **Daftar Panen**: tanggal, musim, greenhouse, total berat, grade
- **Catat Panen**: pilih Musim Tanam (cuma yang berstatus Aktif/Panen
  yang muncul di dropdown), tanggal, lalu isi hasil panen **per
  grade** (bisa lebih dari 1 baris — misal Grade A 50kg + Grade B
  30kg dalam 1 kali catat panen)
- Setiap baris grade otomatis jadi **Stok** yang siap dijual (lewat
  `StockBatchService`, sama persis dengan logic API yang sudah ada
  sebelumnya — tidak dibuat ulang)
- Hapus panen (kalau ada izinnya)

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (+ link "Panen" di sidebar,
                                          desktop & mobile)
routes/web.php                           (+ route /harvests)
```
File baru: `app/Livewire/Harvest/Manage.php`,
`resources/views/livewire/harvest/manage.blade.php`, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=HarvestManageTest
php artisan test
```

## Catatan

Menghapus Panen saat ini **belum ada proteksi** kalau stoknya sudah
terjual (mengikuti perilaku API yang sudah ada — bukan celah baru
dari halaman ini). Bisa diperbaiki terpisah kalau dibutuhkan.

## Selanjutnya (sesuai urutan yang disepakati)

**Stok/Gudang** — supaya bisa langsung verifikasi hasil panen di
atas benar-benar muncul sebagai stok.
