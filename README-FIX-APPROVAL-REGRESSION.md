# Perbaikan: 2 Regresi dari Fitur Approval Beban

## Akar masalah — 2 kasus berbeda, 2 solusi berbeda

**1. `InputStockCostRevisionTest` gagal** — Expense di sini dibuat
**OTOMATIS oleh sistem** (lewat `InputUsageController`, saat mencatat
pemakaian pupuk), bukan diketik manual oleh manusia. Setelah dipikir
ulang, ini **seharusnya otomatis disetujui** — bukan "klaim" yang
perlu diverifikasi orang lain, tapi fakta yang sudah dihitung sistem
dari data pemakaian yang sudah tercatat (jumlah × harga rata-rata
yang sudah beku). Jadi saya perbaiki di CONTROLLER-nya (bukan cuma
test-nya) — pemakaian pupuk sekarang auto-approve begitu dicatat.

**2. `DashboardTest` gagal** — Expense di sini memang benar-benar
diinput manual lewat form/API langsung, jadi INI yang seharusnya
tetap melalui alur approval biasa. Saya perbaiki test-nya supaya
memanggil endpoint approve (`POST /expenses/{id}/approve`) setelah
tiap Expense dibuat, sama seperti yang akan dilakukan Finance/Owner
sungguhan.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/InputUsageController.php   (auto-approve
                                                          expense dari
                                                          pemakaian pupuk)
tests/Feature/Dashboard/DashboardTest.php               (tambah
                                                           panggilan
                                                           approve)
```

## Setelah menyalin
```bash
php artisan test
```
Seharusnya SEMUA test kembali hijau sekarang.

## Kalau nanti nemu Expense::create lain yang perlu approval

Bedakan dulu: kalau itu **manusia yang input** (lewat form/API
langsung) → biarkan "Menunggu" seperti biasa, approve manual. Kalau
itu **sistem yang hitung otomatis** dari data yang sudah ada (seperti
pemakaian pupuk ini) → tambahkan `'approved_at' => now(), 'approved_by' => ...`
langsung di titik pembuatannya, bukan biarkan menunggu approval yang
tidak akan pernah ada gunanya.
