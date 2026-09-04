# Prompt: Pembuatan Landing Page — Laravel 12 (Antigravity)

> File ini berisi prompt siap pakai untuk dijalankan di Antigravity.
> Fokus tahap ini: **Landing Page saja**. Struktur Store & Admin/App
> dikerjakan di sesi terpisah nanti.

---

## Prompt

```
Kamu adalah AI coding agent yang bekerja di dalam project Laravel 12.

## FOKUS TUGAS SAAT INI
Hanya membangun LANDING PAGE dari desain yang sudah saya siapkan.
JANGAN membuat, menyentuh, atau menyiapkan struktur folder/layout untuk
konsep Store maupun Admin/App terlebih dahulu. Cukup fokus 100% ke Landing Page.
Pemisahan layout untuk Store & Admin akan dikerjakan di sesi terpisah nanti.

## ATURAN UTAMA (WAJIB DIPATUHI)
- SETIAP kali kamu akan mengambil keputusan teknis (misalnya: penamaan file/folder,
  struktur komponen, pemilihan pendekatan styling, penempatan asset, cara routing,
  penggunaan Blade component vs partial/include, dsb), STOP dan TANYAKAN dulu ke saya
  sebelum mengeksekusinya. Jangan langsung membuat file atau menulis kode
  tanpa persetujuan saya.
- Jika ada beberapa opsi valid untuk suatu keputusan, sebutkan opsi-opsinya
  beserta kelebihan/kekurangan singkat, lalu tunggu saya memilih.
- Kerjakan secara bertahap (step by step), satu langkah selesai dan disetujui
  dulu baru lanjut ke langkah berikutnya.

## KONTEKS PROJECT
- Framework: Laravel 12
- Project besar nantinya akan punya 3 konsep: Landing Page, Store, Admin/App
  — namun itu di luar scope saat ini.
- Scope saat ini: HANYA Landing Page.

## TECH STACK
- Tailwind CSS DAN Bootstrap digunakan berdampingan dalam project ini.
- Sebelum instalasi/konfigurasi apa pun, tanyakan dulu ke saya:
  - Apakah keduanya perlu aktif bersamaan di landing page, atau
    Tailwind untuk sebagian section dan Bootstrap untuk sebagian lainnya?
  - Bagaimana strategi menghindari konflik class/reset CSS antara
    Tailwind dan Bootstrap (misalnya soal preflight/reset)?
  - Apakah menggunakan Vite bawaan Laravel 12 untuk build asset keduanya?

## HAL YANG PERLU DIKONFIRMASI SEBELUM MULAI CODING
Sebelum menulis file apa pun, tanyakan dulu ke saya untuk hal-hal berikut:
1. Nama controller yang akan dipakai (misal: LandingController) — konfirmasi dulu.
2. Struktur folder view landing page (misal: resources/views/landing/...) —
   ajukan usulan struktur, tunggu persetujuan saya, baru dibuat.
3. Apakah perlu 1 layout master khusus landing (layouts/landing.blade.php)
   atau cukup 1 file blade saja dulu untuk tahap ini.
4. Cara pemecahan section desain menjadi partial/component
   (misal: navbar, hero, fitur, testimoni, CTA, footer) —
   tunjukkan dulu daftar section berdasarkan desain, minta konfirmasi saya.
5. Penempatan asset gambar dari desain (folder mana yang dipakai).
6. Pendekatan SEO dasar (title, meta description, og:tags) — tanyakan
   apakah perlu dari sekarang atau menyusul.

## DESAIN
Desain sudah tersedia dalam bentuk file HTML statis di path:
`docs/index.html` (beserta asset pendukungnya seperti CSS/JS/gambar
di dalam folder `docs/`, jika ada).

Sebelum mengonversi, lakukan ini dulu:
1. Baca dan analisis isi `docs/index.html` secara menyeluruh
   (termasuk file CSS/JS terkait yang dirujuk di dalamnya, dan folder asset seperti
   `docs/css`, `docs/js`, `docs/images`, dsb bila ada).
2. Petakan section-section yang benar-benar ada di file tersebut
   (misal: navbar, hero, fitur, testimoni, CTA, footer, dll — sesuai
   apa yang ADA di HTML, jangan menambah section yang tidak ada).
3. Tunjukkan hasil pemetaan section tersebut ke saya dan MINTA KONFIRMASI
   sebelum mulai memecahnya menjadi partial/component Blade.
4. Identifikasi apakah styling di `docs/index.html` memakai Tailwind,
   Bootstrap, custom CSS, atau kombinasi — laporkan temuannya ke saya
   sebelum memutuskan cara migrasinya ke project Laravel.
5. Identifikasi asset (gambar, font, icon) yang direferensikan di HTML,
   lalu tanyakan ke saya di mana asset tersebut akan dipindahkan
   (misal ke `public/images`, `resources/images`, dsb) sebelum memindahkannya.

Konversi ke Blade harus 1:1 mengikuti markup, struktur, dan styling yang ada
di `docs/index.html` — tidak menambah atau mengurangi konten/section apa pun
tanpa persetujuan saya terlebih dahulu.

## OUTPUT YANG DIHARAPKAN (bertahap, per persetujuan)
1. Rangkuman & pertanyaan konfirmasi untuk setiap keputusan sebelum eksekusi.
2. Setelah disetujui, baru buatkan file terkait satu per satu
   (bukan langsung semua sekaligus).
3. Tidak menyentuh folder/route apa pun di luar scope Landing Page.

Mulai dengan: ajukan pertanyaan-pertanyaan konfirmasi di atas terlebih dahulu
sebelum membuat file atau struktur apa pun.
```

---

## Catatan Penggunaan

- Pastikan project sudah punya folder `docs/index.html` (beserta asset pendukungnya)
  sebelum menjalankan prompt ini di Antigravity — agar AI bisa langsung membacanya.
- AI akan mengajukan pertanyaan konfirmasi terlebih dahulu — jawab satu per satu sebelum lanjut ke tahap coding.
- Struktur folder untuk **Store** dan **Admin/App** sengaja tidak dibuat pada tahap ini.
