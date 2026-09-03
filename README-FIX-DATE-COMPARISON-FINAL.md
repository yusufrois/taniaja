# Perbaikan FINAL: Bug Perbandingan Tanggal di Laporan Akuntansi

## Akar masalah sudah 100% terbukti lewat data mentah

Saya cek langsung ke database (lewat query mentah, bypass Eloquent) —
terbukti nilai `date` di tabel `journal_entries` tersimpan sebagai
`"2026-09-01 00:00:00"`, BUKAN `"2026-09-01"` polos. Cast
`'date' => 'date:Y-m-d'` yang saya pakai di model `JournalEntry`
ternyata **cuma mengatur bagaimana nilainya TAMPIL saat dibaca**,
BUKAN format yang benar-benar tersimpan di database.

**Kenapa baru ketahuan sekarang, padahal sudah lolos banyak test
sebelumnya**: perbandingan `<=` (Neraca) gagal karena secara teks,
`"2026-09-01 00:00:00" <= "2026-09-01"` itu **FALSE** (string yang
lebih panjang dianggap "lebih besar"). Tapi perbandingan `>=` (dipakai
di Buku Besar, Fase L3) **kebetulan selalu benar** untuk kasus yang
sama — bukan karena kodenya beda benar, tapi kebetulan arah
perbandingannya membuat bug ini tidak kelihatan. Jadi Fase L3
sebenarnya PUNYA bug yang sama, cuma belum pernah ketahuan.

## Solusi: `whereDate()`, bukan `where('date', ...)`

`whereDate('date', '<=', $tanggal)` itu fungsi KHUSUS Laravel yang
selalu membandingkan HANYA bagian tanggalnya saja, mengabaikan jam
berapa pun yang tersimpan — jadi aman terlepas dari format
penyimpanan sebenarnya di database.

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/AccountingReportController.php
```
Diperbaiki di **4 titik**: `ledger()` (2 titik, Fase L3 — yang
kemarin "kebetulan lolos" tapi sebenarnya berisiko sama), dan
`balanceSheet()` + `incomeStatement()` (Fase L4).

Test-nya SAMA seperti paket L4 sebelumnya (saya sertakan lagi versi
bersih, tanpa sisa `dump()`/log debug).

## Setelah menyalin
```bash
php artisan test --filter=Accounting
```

## Catatan untuk ke depan — pola yang perlu diwaspadai

Kalau nanti nemu bug SERUPA (angka yang harusnya match jadi 0 atau
kosong, khususnya melibatkan perbandingan tanggal `<=`/`>=`/
`whereBetween`), ini pola yang perlu dicurigai duluan:
- **`whereYear()`/`whereMonth()`** → AMAN (Fase H Penggajian pakai
  ini, tidak terpengaruh, karena fungsi ini ekstrak bagian tanggal
  langsung dari SQL, tidak peduli ada jam atau tidak)
- **`where('kolom_tanggal', $nilai)` tanpa operator (default `=`)**
  → biasanya AMAN, karena Eloquent otomatis menyesuaikan nilai
  pembanding lewat cast model saat operatornya `=` — pola ini yang
  dipakai `updateOrCreate()` di Absensi (Fase G), dan itu memang
  benar-benar teruji jalan.
- **`where('kolom_tanggal', '<=', ...)`, `'>='`, atau
  `whereBetween(...)`** → BERISIKO, ganti ke `whereDate()`.

## Kenapa saya tidak coba "perbaiki" cara penyimpanannya

Saya sempat coba (di Absensi, Fase G) pakai cast `date:Y-m-d` dengan
harapan itu memperbaiki FORMAT PENYIMPANAN — ternyata cuma
memperbaiki cara TAMPILnya saja, bukan penyimpanannya. Daripada
terus mengejar cara "memperbaiki" format penyimpanan (yang saya sudah
buktikan sendiri tidak semudah kelihatannya), solusi `whereDate()` ini
lebih pasti benar karena tidak bergantung sama sekali pada format
penyimpanan sebenarnya — aman apapun yang tersimpan di baliknya.
