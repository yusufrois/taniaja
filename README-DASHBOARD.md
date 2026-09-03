# Phase 9 (bagian 2) — Dashboard Asli (Setup Notes)

## File yang MENIMPA file lama
```
app/Livewire/Dashboard.php                (dari placeholder jadi asli)
resources/views/livewire/dashboard.blade.php  (dari placeholder jadi asli)
resources/views/layouts/app.blade.php     (tambah script Chart.js via CDN)
```
Tidak ada file baru selain test.

## Setelah menyalin
Tidak perlu `npm install` apapun lagi — Chart.js dipakai lewat CDN,
sengaja begitu supaya tidak perlu utak-atik npm lagi setelah drama
Node.js kemarin. Langsung:
```bash
php artisan test --filter=DashboardTest
```
Lalu buka browser, login, dashboard akan otomatis tampil dengan data asli.

## Yang ditampilkan

**Kalau punya permission `report.view`** (Owner/Manager/Finance):
- 6 kartu: Total Revenue, Total Expense, Net Profit, Total Hutang,
  Greenhouse Aktif, Musim Aktif
- Chart 1: Revenue/Expense/Gross Profit 6 bulan terakhir (line chart)
- Chart 2: Expense per Greenhouse, top 5 (bar chart)

**Kalau cuma punya `activity.view`** (Supervisor/Worker):
- 2 kartu: Aktivitas Hari Ini, Aktivitas Terlambat
- TIDAK melihat data finansial sama sekali

**Kalau tidak punya keduanya:** halaman kosong dengan pesan.

Ini sengaja dibuat bertingkat — dashboard TIDAK "polos nampilin semua ke
siapapun yang login". Aksesnya ikut Role & Permission Matrix yang sama
persis dipakai API sejak Phase 8, supaya web UI tidak membocorkan data
yang API-nya sendiri sembunyikan dari role tertentu.

## Keputusan arsitektur penting

1. **`Net Profit` di dashboard memakai definisi YANG SAMA PERSIS**
   dengan laporan resmi di Phase 8 (`Revenue - COGS`, dengan
   `COGS = SUM(SaleItem.cost)`) — BUKAN `Revenue - Total Expense` yang
   naif. Ada test khusus (`test_net_profit_uses_revenue_minus_cogs_...`)
   yang secara sengaja membuat skenario 2 musim (satu sudah panen+jual,
   satu belum) untuk MEMBUKTIKAN kalkulasinya tidak keliru — bukan cuma
   kebetulan angkanya sama.
2. **Chart.js lewat CDN**, bukan `npm install chart.js` — supaya tidak
   menambah lagi dependency npm di toolchain yang sudah sempat
   bermasalah (Node version, Tailwind version). Trade-off: butuh koneksi
   internet saat halaman dibuka (untuk load script dari `cdn.jsdelivr.net`).
   Kalau nanti mau di-bundle lokal, tinggal `npm install chart.js` dan
   ganti tag `<script src="...">` dengan `import Chart from 'chart.js/auto'`
   di `resources/js/app.js`.
3. **Query "expense per greenhouse" pakai pola `where()` yang dibungkus
   closure**, bukan `->where()->orWhereHas()` langsung — mengulang pola
   aman yang sudah ditetapkan di Phase 8 untuk menghindari kebocoran
   tenant isolation lewat precedence AND/OR di SQL.

## Yang BELUM ada (menyusul langkah Phase 9 berikutnya)
- Sidebar navigasi lengkap (Section 31) — saat ini cuma ada navbar atas
  sederhana dengan nama user + tombol keluar
- Halaman CRUD untuk master data, budidaya, keuangan, dst
- Notifikasi in-app (Section 27)
- Form input lapangan mobile-first (Section 32)
