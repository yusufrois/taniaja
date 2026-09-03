# Phase 9 (bagian 3) — Lupa Password (Setup Notes)

## File yang MENIMPA file lama
```
routes/web.php                                          (tambah 2 route)
resources/views/livewire/auth/login.blade.php            (tambah link
                                                            "Lupa password?")
```
Semua file lain BARU. Tidak ada migration baru — tabel
`password_reset_tokens` sudah otomatis ada sejak `laravel new` (bawaan
skeleton Laravel 11/12).

## Setelah menyalin
```bash
php artisan test --filter=PasswordResetTest
```

## PENTING — cara lihat link reset saat testing manual di browser

Kalau `.env` Anda masih `MAIL_MAILER=log` (default Laravel kalau belum
diatur SMTP), email reset **tidak benar-benar terkirim** — link resetnya
malah ditulis ke file log. Setelah klik "Kirim Link Reset" di browser:

1. Buka file `storage/logs/laravel.log`
2. Cari baris paling bawah yang berisi URL seperti
   `http://127.0.0.1:8000/reset-password/xxxxx?email=...`
3. Copy-paste URL itu ke browser untuk lanjut ke halaman reset password

Kalau nanti mau email beneran terkirim (ke Gmail, dst), perlu atur
`MAIL_MAILER=smtp` + kredensial SMTP di `.env` — itu di luar cakupan
sekarang, tidak wajib untuk development.

## Alur

```
/forgot-password  → masukkan email → dapat link (lihat catatan di atas)
/reset-password/{token}?email=...  → masukkan password baru → otomatis
                                       ke halaman login
```

## Keputusan teknis

- Pakai **password broker bawaan Laravel** (`Password::sendResetLink`,
  `Password::reset`), bukan bikin sistem token sendiri — ini fitur
  standar framework yang sudah aman (token di-hash, expired otomatis
  setelah 60 menit default), tidak perlu reinvent.
- `User` model kita otomatis kompatibel karena extends class dasar
  Laravel yang sudah menyertakan `CanResetPassword` — tidak perlu ubah
  model.

## Bug yang saya perbaiki sendiri sebelum paket ini dikirim
Saat menulis `ResetPassword::resetPassword()`, saya sempat menulis
`redirect()->route('login')` tanpa `return` (jadi redirect-nya tidak
akan pernah benar-benar terjadi di Livewire) DAN method-nya masih
dideklarasikan `: void` padahal sekarang bisa mengembalikan response
redirect (yang akan menyebabkan TypeError). Keduanya saya perbaiki
sebelum kode ini pernah dijalankan sama sekali.
