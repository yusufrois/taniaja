# Perbaikan Bug: Variabel Tidak Ter-capture di finalizePeriod()

## File yang MENIMPA file lama
```
app/Services/PayrollCalculationService.php
```
Cuma 1 file, 1 baris.

## Penyebab
Closure `DB::transaction(function () use ($period) {...})` cuma
menangkap `$period`, padahal di dalamnya juga dipakai
`$finalizedByUserId` (parameter method) — variabel itu jadi tidak
dikenal DI DALAM closure (PHP butuh `use()` eksplisit untuk closure
mengakses variabel dari luar dirinya). Diperbaiki jadi
`use ($period, $finalizedByUserId)`.

## Setelah menyalin
```bash
php artisan test --filter=PayrollTest
```
Semua 10 test seharusnya PASS sekarang.
