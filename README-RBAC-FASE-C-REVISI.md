# Revisi Fase C — Biaya Diakui Saat DIPAKAI, Bukan Saat DIBELI

Menjawab pertanyaan: "bagaimana jika antara greenhouse A dan B beda
pemakaian pupuk atau pengeluaran lainnya".

## PENTING — karena Anda SUDAH `php artisan migrate` paket Fase C sebelumnya

Migration di paket ini adalah **migration TAMBAHAN** (ALTER, bukan
CREATE ulang) — aman dijalankan di atas migration Fase C yang sudah
ada. Jangan hapus/ulang migration Fase C yang lama.

## File yang MENIMPA file lama (dari paket Fase C sebelumnya)
```
app/Models/InputItem.php              (+ averageUnitCost(), default_expense_category_id)
app/Models/InputPurchase.php          (- referensi expense_category_id/expense_id)
app/Models/InputUsage.php             (+ cost, expense_id)
app/Http/Requests/StoreInputItemRequest.php     (+ default_expense_category_id)
app/Http/Requests/UpdateInputItemRequest.php    (+ default_expense_category_id)
app/Http/Requests/StoreInputPurchaseRequest.php (- expense_category_id, sekarang opsional tidak dipakai)
app/Http/Resources/InputItemResource.php        (+ average_unit_cost, digembok cost.view)
app/Http/Resources/InputPurchaseResource.php    (unit_price/total_amount digembok cost.view)
app/Http/Resources/InputUsageResource.php       (+ cost, expense_id, digembok cost.view)
app/Http/Controllers/Api/V1/InputPurchaseController.php  (TIDAK LAGI bikin Expense)
app/Http/Controllers/Api/V1/InputUsageController.php     (SEKARANG yang bikin Expense)
tests/Feature/InputStock/InputStockTest.php     (disesuaikan dengan perilaku baru)
```
File baru: 1 migration ALTER, 1 file test baru
(`InputStockCostRevisionTest.php`).

## Setelah menyalin
```bash
php artisan migrate
php artisan test --filter=InputStock
```
(filter `InputStock` mencakup KEDUA file test: yang lama dan yang baru)

## Perubahan inti — jawaban langsung ke pertanyaan Anda

**Sebelumnya**: beli pupuk 50kg (borongan, tidak ditandai greenhouse
manapun) → biaya langsung tercatat sebagai Expense saat itu juga,
menempel di manapun pembelian ditandai (atau tidak kemana-mana kalau
beli borongan).

**Sekarang**: beli pupuk TIDAK langsung bikin Expense. Baru saat
DIPAKAI (`POST /input-usages`, dengan `greenhouse_id` masing-masing),
sistem hitung biayanya (`quantity × harga rata-rata saat itu`) dan bikin
Expense yang menempel ke greenhouse YANG BENAR-BENAR MEMAKAINYA.

**Contoh konkret** (dari test `test_different_greenhouses_using_pooled_stock_get_separately_attributed_cost`):
- Beli 50kg NPK @ Rp20.000 sekali borongan (tidak ditandai greenhouse manapun)
- GH-A pakai 15kg → Expense GH-A = Rp300.000
- GH-B pakai 20kg → Expense GH-B = Rp400.000
- Laporan `GET /reports/greenhouses/{id}/performance` masing-masing
  menunjukkan biaya yang BENAR sesuai pemakaian aktual, bukan menempel
  di satu tempat saja

## Harga rata-rata tertimbang (weighted average)

Kalau beli 2x dengan harga beda (mis. 50kg @ Rp20.000, lalu 50kg @
Rp30.000), sistem otomatis hitung rata-rata tertimbang: **(50×20.000 +
50×30.000) ÷ 100 = Rp25.000/kg**. Ini dipakai untuk menghitung biaya
pemakaian berikutnya.

## Biaya DIBEKUKAN saat dipakai (tidak berubah mundur)

Kalau sudah pakai 10kg saat harga rata-rata Rp20.000/kg (biaya tercatat
Rp200.000), lalu BELAKANGAN ada pembelian baru yang menaikkan
rata-rata jadi lebih mahal — biaya yang SUDAH tercatat itu **tidak
ikut berubah**. Prinsip yang sama seperti `unit_cost` di modul Panen/
Dagang (Phase 6): dibekukan begitu tercatat, tidak dihitung ulang
mundur.

## Kalau InputItem tidak diberi `default_expense_category_id`

Stok tetap tercatat dan berkurang seperti biasa saat dipakai, TAPI
tidak ada Expense yang dibuat otomatis (karena sistem tidak tahu mau
dikategorikan ke mana). Cocok kalau Anda cuma mau lacak stok fisiknya
saja untuk sebagian item, tanpa masuk ke laporan biaya.
