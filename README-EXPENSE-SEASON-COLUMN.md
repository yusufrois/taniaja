# Perbaikan: Kolom "Musim" di Tabel Beban

## Alasan

1 Greenhouse bisa punya banyak Musim Tanam dari waktu ke waktu —
kolom "Greenhouse" saja ("GH-A") tidak cukup untuk tahu ini biaya
Musim 1 atau Musim 2. Sekarang ada kolom **"Musim"** terpisah,
menampilkan nama musim (misal "Musim 2 - GH A"), atau "-" kalau
Beban itu tidak dikaitkan ke musim tertentu (murni operasional
perusahaan).

## File yang MENIMPA file lama
```
app/Livewire/Expense/Manage.php
resources/views/livewire/expense/manage.blade.php
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=ExpenseSeasonColumnTest
```

## Soal Dashboard (bukan bug)

Sesuai pertanyaan Anda: Dashboard memang **sengaja** menampilkan
gabungan semua musim & GH — itu untuk gambaran perusahaan secara
keseluruhan. Untuk lihat per musim secara terpisah (Musim 1 vs Musim
2), gunakan tombol **"Detail"** di halaman Musim Tanam.
