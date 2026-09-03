# Roadmap Tambahan Fase L3 — Buku Besar & Neraca Saldo

Murni laporan baca-saja dari data yang sudah ada sejak L1/L2 — **tidak
ada tabel baru, tidak ada permission baru** (pakai `accounting.view`/
`accounting.create` yang sudah ada).

## File yang MENIMPA file lama
```
routes/api.php   (+ 2 route baru)
```
File baru: 1 controller, 1 test.

## Setelah menyalin
```bash
php artisan test --filter=LedgerAndTrialBalanceTest
```

## Endpoint baru

**Buku Besar per akun:**
```
GET /api/v1/accounting/ledger/{chart_of_account_id}
GET /api/v1/accounting/ledger/{chart_of_account_id}?from=2026-08-01&to=2026-08-31
```
Menampilkan semua baris jurnal yang pernah masuk ke akun itu, urut
tanggal, dengan **saldo berjalan** (running balance) di tiap baris.

**Neraca Saldo:**
```
GET /api/v1/accounting/trial-balance
```
Semua akun yang punya aktivitas (akun dengan saldo nol otomatis
disembunyikan), plus pengecekan `is_balanced` — total kolom Debit
harus sama dengan total kolom Kredit.

## Kenapa `is_balanced` HARUS SELALU `true`

Karena `JournalEntryService` (Fase L1) sudah menolak jurnal yang tidak
seimbang SEJAK AWAL dibuat, Neraca Saldo across SEMUA akun **otomatis
selalu seimbang** — ini bukan kebetulan, ini konsekuensi matematis dari
aturan yang sudah ditegakkan sejak L1. Endpoint ini sekaligus jadi
"tes kewarasan" otomatis: kalau suatu saat `is_balanced` jadi `false`,
itu tanda ada yang salah di tempat lain (mis. ada kode yang bikin
jurnal langsung tanpa lewat `JournalEntryService`).

## Lanjut ke roadmap

L1, L2, L3 selesai. Berikutnya L4 (Neraca & Laba Rugi format resmi).
Setelah L4-L5 selesai, baru Fase F (rangkai role siap pakai) — fase
paling akhir dari SELURUH roadmap besar ini.
