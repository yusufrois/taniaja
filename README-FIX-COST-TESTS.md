# Perbaikan Test Lama Setelah RBAC Fase B

## File yang MENIMPA file lama
```
tests/Feature/Stock/TradingWorkflowTest.php
tests/Feature/Sales/SalesWorkflowTest.php
```

## Kenapa 2 test ini gagal
Bukan bug baru — ini konsekuensi WAJAR dari Fase B: `unit_cost` dan
`total_profit` sekarang cuma muncul di response kalau user punya
`cost.view`. User `finance`/`owner` di 2 test ini sebelumnya tidak
diberi permission itu (belum ada saat test ditulis), jadi field yang
dicek jadi `null` (hilang). Solusinya cuma tambah `cost.view` ke daftar
permission user tersebut — tidak ada perubahan logic aplikasi.

Saya juga sudah cek SEMUA test file lain (`ReportsTest`, `DashboardTest`)
yang menyebut `unit_cost`/`total_profit` — keduanya cuma di komentar,
bukan assertion JSON, jadi aman tidak perlu diubah.

## Setelah menyalin
```bash
php artisan test
```
Semua 82 test seharusnya PASS sekarang.
