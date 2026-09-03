# Perbaikan: Halaman Budidaya Sekarang Benar-Benar Terkunci (Bukan Cuma Link Hilang)

## Solusi

Halaman **Greenhouse**, **Musim Tanam**, dan **Detail Musim Tanam**
sekarang mengecek status modul "Budidaya" di `mount()` — begitu
modul dimatikan, akses LANGSUNG via URL (bukan cuma klik sidebar)
akan mendapat halaman **404** ("Halaman ini bagian dari modul yang
sedang dimatikan..."), bukan halaman kebuka normal seperti sebelumnya.

Logic pengecekannya di 1 trait (`RequiresEnabledModule`) yang dipakai
bersama oleh ketiga halaman — supaya nanti kalau ada modul lain yang
perlu dikunci serupa (misal Pembelian, begitu UI-nya ada), tinggal
`use RequiresEnabledModule;` + panggil `$this->ensureModuleEnabled('nama_modul')`
di `mount()`-nya.

## File yang MENIMPA file lama
```
app/Livewire/Greenhouse/Manage.php
app/Livewire/Season/Manage.php
app/Livewire/Season/Detail.php
```
File baru: `app/Livewire/Concerns/RequiresEnabledModule.php`, 1 test.

## Setelah menyalin
```bash
php artisan view:clear
php artisan test --filter=ModuleGateTest
php artisan test
```

## Cara coba manual

1. Matikan toggle "Budidaya" di Pengaturan Modul, simpan
2. Coba ketik langsung `http://127.0.0.1:8000/greenhouses` atau
   `/seasons` di address bar
3. Seharusnya muncul halaman 404, bukan halaman normal
4. Nyalakan lagi togglenya — coba lagi, halaman harus kembali normal
