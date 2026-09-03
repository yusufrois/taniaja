# Phase 9 UI — Halaman Hutang (Debt)

Halaman ketiga di grup Keuangan, sedikit lebih kompleks — 2 modal
terpisah: catat hutang baru, dan catat pembayaran cicilan.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   ("Hutang" jadi link aktif,
                                          2 tempat)
routes/web.php                           (+ route /debts)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=DebtManageTest
```

## Fitur khusus

- **Pembayaran cicilan dicatat terpisah** dari hutangnya sendiri
  (bukan cuma angka "sudah dibayar" yang ditimpa) — riwayat lengkap
  tiap cicilan tersimpan, sama seperti di API.
- **Tidak bisa bayar lebih dari sisa hutang** — sama seperti aturan
  API, sudah diuji khusus.
- Status otomatis: **Belum Bayar / Sebagian / Lunas / Jatuh Tempo** —
  dihitung live dari total dibayar vs jatuh tempo, bukan disimpan
  manual.
- Tombol "Bayar" otomatis hilang begitu status jadi Lunas.

## Lanjut

Sisa grup Keuangan: **Aset Tetap**. Sisa sidebar: **Laporan**.
