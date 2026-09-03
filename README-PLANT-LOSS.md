# Fitur Tambahan — Tanaman Mati / Plant Loss (Setup Notes)

Disisipkan atas permintaan Anda, di antara Phase 8 dan Phase 9.

## File yang MENIMPA file lama
```
routes/api.php                                   (nambah 3 endpoint plant-loss)
database/seeders/RolePermissionSeeder.php        (tambah modul 'plant_loss')
database/seeders/RoleCapabilitySeeder.php        (assign plant_loss ke Owner/Manager/Supervisor/Worker)
app/Models/Season.php                            (tambah relasi plantLosses() + accessor
                                                    current_plant_count & survival_rate_percent)
app/Http/Resources/SeasonResource.php            (tambah 2 field baru di response)
app/Http/Controllers/Api/V1/SeasonController.php (dashboard tambah current_plant_count
                                                    & survival_rate_percent)
```
Semua file lain BARU.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=PlantLossTest
```

## Cara pakai

```
POST /api/v1/plant-losses
{
  "season_id": 3,
  "date": "2026-08-20",
  "quantity": 50,
  "cause": "Penyakit layu fusarium",
  "notes": "Ditemukan di blok tengah greenhouse"
}
```
- Tidak boleh mencatat jumlah mati lebih besar dari tanaman yang **masih
  hidup saat ini** (ditolak 422 kalau melebihi)
- `Season.plant_count` (jumlah awal tanam) **TIDAK PERNAH berubah** —
  itu tetap catatan sejarah "berapa yang ditanam". Yang berubah adalah
  angka turunan baru: `current_plant_count` (masih hidup) dan
  `survival_rate_percent` (persentase yang bertahan)

**Lihat riwayat kematian per musim:**
`GET /api/v1/seasons/{id}/plant-losses`

**Hapus catatan yang salah input:**
`DELETE /api/v1/plant-losses/{id}` — otomatis mengembalikan
`current_plant_count` (karena dihitung live dari total semua catatan,
bukan angka yang disimpan terpisah)

## Kenapa desainnya begini

1. **`current_plant_count` dihitung live** (`plant_count` dikurangi total
   semua `PlantLoss.quantity`), bukan kolom tersimpan — pola yang sama
   persis dengan HST (Phase 3), status Hutang/Pembelian/Penjualan
   (Phase 5-7). Konsisten: tidak ada angka turunan di aplikasi ini yang
   disimpan sebagai kolom terpisah yang bisa "ketinggalan sinkron".
2. **Tabel terpisah, bukan ditumpuk ke `Activity`.** Activity (Phase 4)
   memang bisa mencatat kejadian sebagai teks bebas, tapi jumlah yang
   mati perlu jadi angka terstruktur supaya bisa dihitung
   (`current_plant_count`, `survival_rate_percent`) — bukan terkubur di
   deskripsi teks yang tidak bisa dijumlahkan sistem.
3. **Validasi "tidak boleh melebihi yang masih hidup"** — pola yang sama
   dengan validasi pembayaran hutang/pembelian/invoice (Phase 5-7) dan
   penjualan stok (Phase 6): tidak mungkin tercatat kondisi yang secara
   logis mustahil (mati lebih banyak dari yang hidup).
4. **Permission**: Owner/Manager/Supervisor/Worker dapat akses (pola
   sama dengan `harvest`/`activity` — yang di lapangan yang mencatat),
   Finance sengaja TIDAK dapat (bukan urusan keuangan langsung).

## Proses verifikasi sebelum paket ini dikirim
- Semua permission di setiap test diaudit manual satu-satu terhadap
  `authorize()` call yang sebenarnya di controller (bukan disalin dari
  test lain)
- Dicek tidak ada pola `where()->orWhere()` tanpa closure (jebakan
  tenant-isolation yang ditemukan di Phase 8 kemarin)
- Semua assertion angka pakai `assertEquals` (longgar), bukan
  `assertJsonPath` dengan literal desimal (yang berulang kali jadi
  sumber false-positive bug sebelumnya)
- Brace balance dicek di semua file sebelum di-zip
