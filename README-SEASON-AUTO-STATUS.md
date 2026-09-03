# Fitur: Status Musim Tanam Otomatis

Sesuai yang Anda minta:
- **Perencanaan → Aktif**: otomatis, saat tanggal tanam sudah tiba/lewat
- **→ Panen**: otomatis, saat Tanggal Panen Aktual diisi (tidak perlu
  pilih status manual lagi)
- **Selesai**: tetap manual — Anda pilih sendiri kapan musim itu
  resmi ditutup, sebelum mulai musim baru
- **Dibatalkan**: tetap manual juga

## File yang MENIMPA file lama
```
app/Livewire/Season/Manage.php
resources/views/livewire/season/manage.blade.php
```
File baru: 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=SeasonAutoStatusTest
```

## Cara kerja teknis

**Perencanaan → Aktif**: dicek setiap kali halaman Musim Tanam
dibuka (bukan pakai cron/jadwal terpisah) — musim yang tanggal
tanamnya sudah lewat langsung dipromosikan begitu ada yang buka
halaman ini. Konsekuensinya: kalau TIDAK ADA yang buka halaman ini
di hari H, statusnya baru berubah begitu ada yang buka halaman
berikutnya (bukan tepat jam 00:00 hari itu) — cukup untuk kebutuhan
sehari-hari, tidak perlu setup jadwal terpisah di server.

**→ Panen**: dicek setiap kali Anda klik Simpan di form edit — kalau
kolom "Tanggal Panen Aktual" terisi (dan statusnya bukan Selesai/
Dibatalkan), otomatis jadi Panen.

**Selesai/Dibatalkan tidak pernah "ditimpa" otomatis** — begitu Anda
pilih salah satunya, sistem tidak akan lagi mengubahnya sendiri,
walaupun tanggal tanamnya sudah lama lewat. Ini sudah diuji khusus.

## Dropdown status di form

Sekarang cuma **Perencanaan, Selesai, Dibatalkan** yang bisa dipilih
manual — "Aktif" dan "Panen" tetap ditampilkan di dropdown (supaya
kelihatan statusnya kalau sedang di situ) tapi tidak bisa dipilih
sendiri, karena memang otomatis.
