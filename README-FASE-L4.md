# Roadmap Tambahan Fase L4 — Neraca & Laba Rugi Formal

Bagian TERAKHIR dari roadmap Akuntansi Double-Entry (L1-L4). Masih
murni laporan baca-saja, tidak ada tabel/permission baru.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/AccountingReportController.php  (+ 2 method)
routes/api.php                                                (+ 2 route)
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan test --filter=Accounting
```

## Endpoint baru

**Neraca (Balance Sheet):**
```
GET /api/v1/accounting/balance-sheet
GET /api/v1/accounting/balance-sheet?as_of=2026-08-31
```

**Laba Rugi (Income Statement):**
```
GET /api/v1/accounting/income-statement?from=2026-08-01&to=2026-08-31
```

## Bagian paling penting: kenapa Neraca selalu seimbang TANPA tutup buku

Sistem ini **sengaja tidak** membuat jurnal penutup periode (proses
akuntansi formal memindahkan saldo Pendapatan/Beban ke Laba Ditahan
di akhir periode) — itu proses akuntansi sungguhan yang di luar
cakupan sekarang. Supaya Neraca tetap `Aset = Kewajiban + Modal`
meski belum pernah "ditutup", saya tambahkan baris otomatis **"Laba
Tahun Berjalan (belum ditutup)"** di bagian Modal — dihitung live dari
Pendapatan dikurangi Beban yang masih terbuka. Ini konsekuensi
matematis langsung dari Neraca Saldo yang selalu seimbang (Fase L3):
`Aset = Kewajiban + Modal + (Pendapatan − Beban)`.

Sudah ada test yang membuktikan ini (`test_balance_sheet_balances_including_unclosed_current_earnings`)
lewat kombinasi modal + penjualan + beban sekaligus.

## Yang SENGAJA tidak dibuat

- **Jurnal penutup periode** (memindahkan Pendapatan/Beban ke Laba
  Ditahan secara resmi di akhir bulan/tahun) — proses akuntansi
  formal yang butuh keputusan bisnis (kapan periode ditutup, siapa
  yang berwenang) di luar cakupan sistem ini saat ini.
- **Perbandingan multi-periode** (Neraca bulan ini vs bulan lalu
  berdampingan) — laporan tunggal per waktu dulu, perbandingan bisa
  ditambah kalau dibutuhkan.

## SELESAI: Seluruh roadmap Akuntansi (L1-L4)

Dengan ini, fondasi akuntansi double-entry lengkap: Bagan Akun,
Jurnal (manual + otomatis), Buku Besar, Neraca Saldo, Neraca, dan
Laba Rugi — semuanya saling terhubung dan konsisten secara matematis.

Sisa dari roadmap L: **L5 (Kas & Bank — dukungan banyak akun +
transfer antar akun)**, lalu **Fase F (rangkai role siap pakai)** —
paling akhir dari SELURUH roadmap besar sejak awal percakapan ini.
