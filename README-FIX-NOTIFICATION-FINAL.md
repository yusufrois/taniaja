# Perbaikan FINAL: Notification Mark-as-Read

## File yang MENIMPA file lama
```
app/Http/Controllers/Api/V1/NotificationController.php   (bungkus manual)
app/Http/Resources/NotificationResource.php               (bersih dari log sementara)
tests/Feature/Task/TaskTest.php                            (bersih dari dump() sementara)
```

## Akar masalah (sejauh yang terkonfirmasi)
Lewat proses debug panjang (log bertahap + dump respons mentah),
terbukti: seluruh proses server (update database, model Eloquent,
array yang dikembalikan Resource) **semuanya benar** di setiap
langkah. Masalahnya ternyata di titik PALING AKHIR — saat Laravel
mengubah objek Resource jadi respons HTTP, endpoint `markRead()` ini
**tidak ikut dibungkus** `{"data": {...}}` seperti endpoint lain di
seluruh aplikasi (yang otomatis dibungkus begitu oleh Laravel).

Saya belum berhasil pastikan 100% KENAPA endpoint ini spesifik
berbeda dari puluhan endpoint lain yang polanya identik (`return new
XxxResource(...)`) tapi selalu dibungkus benar. Yang saya lakukan:
alih-alih terus menebak akar sebabnya, saya **hilangkan
ketergantungan pada perilaku otomatis itu** dengan membungkus manual
(`response()->json(['data' => ...])`) — ini pasti benar terlepas dari
mekanisme di baliknya.

## Setelah menyalin
```bash
php artisan test --filter=TaskTest
```
Semua 9 test seharusnya PASS.

## Kalau nanti nemu bug serupa di endpoint lain
Kalau ada endpoint LAIN yang responsnya juga tidak ke-bungkus "data"
padahal harusnya (gejala: test gagal dengan pesan aneh "null is not
null" padahal data sudah pasti benar), pola perbaikan yang sama bisa
dipakai: ganti `return new XxxResource($model);` jadi
`return response()->json(['data' => new XxxResource($model)]);`
