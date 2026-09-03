# Perbaikan: Regresi di Expense.php (purchase_id hilang)

## Penyebab — murni salah saya

Saat membangun Fase L5, saya pakai `Expense.php` dari **Phase 5**
sebagai basis. Ternyata field `purchase_id` (dipakai fitur "landed
cost" — biaya transport dkk yang ditambahkan ke harga pokok barang)
baru ditambahkan di **Phase 6**, SETELAH Phase 5. Jadi tanpa sadar,
paket L5 saya kemarin MENGHAPUS field itu dari `$fillable`, membuat
pembelian yang dikaitkan dengan biaya tambahan jadi tidak lagi
terhitung dengan benar.

Saya sudah cek — ini kasus TERISOLASI, cuma `Expense.php` yang
bermasalah. 4 model lain yang saya ubah di L5 (`Debt`,
`CapitalTransaction`, `SalePayment`, `PurchasePayment`,
`DebtPayment`) masing-masing cuma punya SATU versi sepanjang proyek
ini, jadi tidak ada risiko regresi serupa di situ.

## File yang MENIMPA file lama
```
app/Models/Expense.php
```

## Setelah menyalin
```bash
php artisan test --filter=TradingWorkflowTest
php artisan test --filter=Accounting
php artisan test --filter=CashAndBankTest
```
Semuanya seharusnya PASS sekarang.
