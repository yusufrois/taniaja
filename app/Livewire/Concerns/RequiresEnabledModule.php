<?php

namespace App\Livewire\Concerns;

/**
 * Roadmap tambahan — "Pengaturan Modul" toggle sebelumnya cuma
 * menyembunyikan link sidebar, TIDAK benar-benar mengunci halamannya
 * — seseorang yang tahu/ketik URL langsung masih bisa membukanya.
 * Ini menutup celah itu: setiap halaman yang tergantung pada modul
 * tertentu memanggil ensureModuleEnabled() di mount()-nya, sehingga
 * benar-benar terkunci (404) begitu modul itu dimatikan, bukan cuma
 * hilang dari menu.
 */
trait RequiresEnabledModule
{
    protected function ensureModuleEnabled(string $module): void
    {
        abort_unless(
            auth()->user()->company->hasModuleEnabled($module),
            404,
            'Halaman ini bagian dari modul yang sedang dimatikan untuk perusahaan Anda. Nyalakan lagi di Pengaturan Modul kalau dibutuhkan.'
        );
    }
}
