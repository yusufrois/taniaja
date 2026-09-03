# Tambahan #2: Jadwal Cicilan Hutang Otomatis

Fitur paling besar dari 3 permintaan tambahan — jadwal bulan-per-bulan
otomatis, penanda "cicilan ke berapa", dan tampilan riwayat lengkap
semua pembayaran.

## File yang MENIMPA file lama
```
app/Models/Debt.php                          (+ field installment_months,
                                               + 3 accessor/method baru)
app/Http/Requests/StoreDebtRequest.php       (+ validasi installment_months)
app/Http/Resources/DebtResource.php          (+ field baru di response API)
app/Livewire/Debt/Manage.php                 (+ field form, + modal Detail)
resources/views/livewire/debt/manage.blade.php  (+ kolom Cicilan, +
                                                   tombol Detail, + modal
                                                   jadwal & riwayat)
```
File baru: 1 migration, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan test --filter=DebtInstallmentTest
```

## Cara pakai

**Saat catat hutang baru**: isi "Jangka Waktu (bulan)" — misal `24`
untuk cicilan 2 tahun. Kalau dikosongkan, hutang berlaku seperti
biasa (bayar kapan saja, jumlah bebas) — **tidak mengubah perilaku
hutang yang sudah ada sebelumnya**.

**Jadwal dihitung otomatis** (bukan disimpan manual): jumlah per
cicilan = total ÷ jumlah bulan, jatuh tempo tiap bulan mulai 1 bulan
setelah tanggal pinjam. Klik **"Detail"** di daftar hutang untuk lihat:
- Tabel jadwal 24 bulan lengkap, status Lunas/Belum tiap bulan
- Riwayat SEMUA pembayaran yang pernah dicatat (tanggal, jumlah, catatan)

Di daftar utama juga muncul badge ringkas, misal **"3/24 bln"**.

## Cara kerja "penanda cicilan ke berapa"

Sengaja **tidak** mengaitkan tiap pembayaran ke nomor cicilan
tertentu (itu perlu ubah struktur data lebih besar) — sebagai
gantinya, total yang SUDAH dibayar dialokasikan berurutan ke jadwal:
kalau sudah bayar Rp3.000.000 dan per-cicilan Rp1.000.000, otomatis
dianggap "cicilan 1-3 lunas", walau pembayarannya sebenarnya cuma 1x
transaksi besar. Ini cukup untuk kebutuhan pemantauan sehari-hari
tanpa perlu tracking rumit per cicilan.

## Yang SENGAJA belum dibuat

- **Pengingat/notifikasi jatuh tempo** — belum ada sistem notifikasi
  otomatis kalau cicilan bulan ini belum dibayar.
- **Ubah jangka waktu setelah hutang dibuat** — kalau salah isi jumlah
  bulan, untuk sekarang hapus & catat ulang (sama seperti prinsip
  "tidak ada edit" di modul Hutang secara umum).

## Lanjut

Dengan ini, ketiga permintaan tambahan (#1 Beban approval, #2 Jadwal
cicilan, #3 Kategori Aset) sudah selesai semua. Sisa dari SELURUH
Phase 9: cuma **Laporan**.
