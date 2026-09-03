# Phase 9 UI — Halaman Beban (Expense) + Grup "Keuangan" di Sidebar

Halaman pertama di area **Keuangan**. Sidebar "Keuangan" sekarang jadi
grup (seperti "Data Master") — "Beban" sudah aktif, "Modal/Hutang/
Aset Tetap" masih placeholder untuk sesi berikutnya.

## File yang MENIMPA file lama
```
resources/views/layouts/app.blade.php   (logo besar + favicon dari
                                          permintaan Anda sebelumnya,
                                          PLUS grup "Keuangan" baru)
routes/web.php                           (+ route /expenses)
```
File baru: 1 komponen Livewire, 1 view Blade, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=ExpenseManageTest
```

## Fitur khusus halaman ini

- **Approve terpisah dari input** — sesuai Matrix Role: Supervisor
  bisa CATAT beban tapi tidak bisa MENYETUJUI, Finance bisa keduanya.
  Tombol "Setujui" cuma muncul untuk yang punya `expense.approve`.
- **Nominal disembunyikan tanpa `cost.view`** — sama seperti aturan
  di seluruh sistem sejak Fase B: Supervisor bisa lihat ada
  catatan beban, tapi tidak lihat angka rupiahnya.
- **Pilih akun pembayaran** (Fase L5) — opsional, kalau tidak dipilih
  otomatis pakai Kas default, sama seperti di API.

## Yang SENGAJA belum dibuat di form ini

Supaya form tidak terlalu panjang untuk permulaan, field-field ini
BELUM ada di UI (tapi tetap bisa lewat API kalau dibutuhkan):
- Kaitan ke Supplier / Purchase tertentu (landed cost)
- Nomor transaksi manual
- Upload lampiran/bukti (`attachment_path`)

## Lanjut

Sisa grup Keuangan: **Modal, Hutang, Aset Tetap**. Sisa sidebar
lainnya: **Laporan**.
