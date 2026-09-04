# Halaman Baru: Stok / Gudang (Fase UI-1, Item #3)

## Isi halaman

- **Daftar stok**: tanggal masuk, sumber (🌾 Panen Sendiri / 🛒
  Hasil Beli — bisa difilter), komoditas, grade, jumlah tersedia,
  harga pokok (kalau punya izin `cost.view`)
- **Jual Cepat**: klik di baris stok manapun untuk langsung jual
  sebagian/semua stok itu ke pelanggan — pakai
  `StockBatchService::sell()` yang sudah ada, otomatis hitung untung
  (revenue - cost)
- Validasi bawaan: tidak bisa jual lebih dari stok yang tersedia

## Verifikasi silang dengan Panen

Karena halaman ini dan halaman Panen berbagi service yang sama
(`StockBatchService`), setiap kali Anda catat Panen, hasilnya
LANGSUNG muncul di sini sebagai stok baru — bisa langsung dicek.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (+ link "Stok / Gudang")
routes/web.php                           (+ route /stock)
```
File baru: `app/Livewire/Stock/Manage.php`,
`resources/views/livewire/stock/manage.blade.php`, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=StockManageTest
php artisan test
```

## Catatan

"Jual Cepat" ini beda dari halaman **Penjualan** (invoice resmi,
belum kita bangun) — ini untuk transaksi cepat 1 batch ke 1
pelanggan tanpa perlu bikin invoice formal. Keduanya akan hidup
berdampingan, sesuai desain API yang sudah ada sejak awal.

## Selanjutnya

**Penjualan** (invoice) — menutup alur inti tanam → panen → jual.
