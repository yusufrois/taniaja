# Roadmap Tambahan Fase L1 — Bagan Akun + Infrastruktur Jurnal

Sub-fase pertama dari sistem akuntansi double-entry penuh. Bagan Akun
dan format ini **dibuat berdasarkan pola umum UMKM pertanian, BUKAN
hasil konsultasi akuntan bersertifikasi** — sebaiknya diperiksa
akuntan sebelum dipakai untuk keperluan pajak/audit sungguhan.

## File yang MENIMPA file lama
```
app/Services/Auth/CompanyRegistrationService.php   (+ auto-seed Bagan
                                                      Akun untuk company
                                                      baru)
routes/api.php                                      (+ 6 route baru)
database/seeders/RolePermissionSeeder.php           (+ modul accounting)
database/seeders/RoleCapabilitySeeder.php            (assign ke
                                                       Owner/Manager/
                                                       Finance)
```
File baru: 3 migration, 3 model, 2 policy, 2 service, 2 request,
3 resource, 2 controller, 1 test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=AccountingTest
```

## Konsep inti

**Chart of Account (Bagan Akun)** — 16 akun standar, otomatis dibuat
untuk company BARU saat daftar. Untuk company yang SUDAH ADA (kayak
LadangWohIjo demo Anda), jalankan sekali:
```
POST /api/v1/chart-of-accounts/seed-defaults
```
(aman dipanggil berkali-kali, tidak akan duplikat)

**Journal Entry (Jurnal)** — setiap transaksi = minimal 2 baris
(Debit & Kredit), **wajib seimbang** (total Debit = total Kredit).
Kalau tidak seimbang, otomatis ditolak (422) dengan pesan jelas
berapa selisihnya.

```json
POST /api/v1/journal-entries
{
  "date": "2026-09-09",
  "description": "Setoran modal awal",
  "lines": [
    {"chart_of_account_id": 1, "debit": 5000000, "credit": 0},
    {"chart_of_account_id": 11, "debit": 0, "credit": 5000000}
  ]
}
```

**Saldo akun** (`GET /chart-of-accounts/{id}`) dihitung LIVE dari
semua jurnal yang pernah diposting ke situ — bukan angka tersimpan
yang bisa "ketinggalan sinkron", sama seperti pola di seluruh sistem
ini (stok, sisa hutang, dst).

## Bug yang saya perbaiki sendiri sebelum paket ini dikirim

Saya sempat tulis desain di mana **membatalkan (void) jurnal tidak
benar-benar mengeluarkan angkanya dari saldo akun** — baris jurnal
punya status "soft-delete" sendiri yang terpisah dari status
induknya. Saya perbaiki: `JournalEntryController::destroy()` sekarang
sengaja void SEMUA baris di dalamnya juga, bukan cuma induknya —
sudah ada test (`test_voiding_a_journal_entry_removes_it_from_account_balance`)
yang membuktikan ini benar SEBELUM saya kirim, bukan ditemukan
belakangan lewat laporan Anda.

## Sengaja BELUM dibuat di L1 ini

- **Auto-jurnal dari modul yang sudah ada** (Sale, Purchase, Expense,
  dst) — itu Fase L2, langkah berikutnya. Untuk sekarang, SEMUA
  jurnal masih manual.
- **Buku Besar & Neraca Saldo** (laporan rekap per akun) — Fase L3.
- **Neraca & Laba Rugi formal** — Fase L4.
- **Penyusutan aset tetap** — belum ada jadwal depresiasi di `Asset`
  yang sudah ada sejak Phase 5, jadi akun "Aset Tetap" saldo-nya
  murni nilai beli, belum dikurangi penyusutan. Ini juga area yang
  sebaiknya dikonfirmasi akuntan.

## Lanjut ke roadmap

Setelah ini teruji jalan, lanjut Fase L2 (auto-jurnal dari transaksi
yang sudah ada). Setelah semua L1-L5 selesai, baru Fase F (rangkai
role siap pakai) — fase paling akhir dari seluruh roadmap.
