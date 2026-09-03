# RBAC Fase A4 — Peringatan & Suspend User (Setup Notes)

Menjawab: "saya sebagai pemilik harus punya otoritas penuh, bisa ngasih
peringatan atau suspend user yang melanggar".

## File yang MENIMPA file lama
```
app/Models/User.php                                (tambah relasi warnings())
app/Http/Controllers/Api/V1/UserController.php     (tambah 4 method baru)
routes/api.php                                      (tambah 4 endpoint)
database/seeders/RolePermissionSeeder.php           (tambah user.warn)
database/seeders/RoleCapabilitySeeder.php            (Owner dapat user.warn)
```
File baru: migration `user_warnings`, model, request, resource, test.

## Setelah menyalin
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=UserWarningSuspendTest
```

## Endpoint baru
```
GET   /api/v1/users/{id}/warnings     — riwayat peringatan user itu
POST  /api/v1/users/{id}/warnings     — kasih peringatan baru
PATCH /api/v1/users/{id}/suspend      — nonaktifkan sementara
PATCH /api/v1/users/{id}/reactivate   — aktifkan lagi
```

## Beda `suspend` vs `DELETE` (dari Fase A3)

| | `PATCH /suspend` | `DELETE /users/{id}` |
|---|---|---|
| Status jadi | inactive | inactive |
| Soft-delete? | TIDAK | YA |
| Masih muncul di daftar staff? | Ya (ditandai nonaktif) | Tidak |
| Bisa dibalikin? | Ya, tinggal `/reactivate` | Lebih permanen |
| Cocok untuk | "Ditahan sementara sambil diperiksa" | "Sudah keluar dari perusahaan" |

Login tetap ditolak (403) untuk kedua kondisi — bedanya cuma soal
kelihatan/tidaknya di daftar staff dan kemudahan dibalikin.

## Kenapa `user.warn` permission TERPISAH dari `user.update`

Supaya nanti kalau Anda mau, Manager bisa diberi izin "boleh kasih
peringatan ke timnya" TANPA otomatis dapat izin penuh kelola staff
(bikin akun baru, ganti role, suspend). Dua kewenangan yang beda level,
jadi permission-nya juga dipisah — bukan dipaksa jadi satu.

## Yang SENGAJA belum saya buat (biar tidak menebak kebutuhan)

- **Auto-suspend setelah N peringatan** — saat ini murni manual, Owner
  yang lihat riwayat peringatan lalu putuskan sendiri kapan suspend.
  Kalau mau otomatis (mis. "3 peringatan = auto suspend"), itu perlu
  aturan bisnis yang lebih spesifik dulu dari Anda.
- **Cabut/hapus peringatan** — saat ini bersifat catatan permanen
  (append-only), tidak bisa dihapus. Kalau perlu fitur "batalkan
  peringatan yang salah input", saya bisa tambahkan.

## Lanjut ke roadmap

Fase A, A2, A3, A4, B semuanya selesai. Berikutnya Fase C (stok input
pertanian/pupuk).
