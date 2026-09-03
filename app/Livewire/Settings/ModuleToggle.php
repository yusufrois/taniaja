<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Roadmap tambahan — "toggle per modul" untuk petani vs tengkulak vs
 * campuran (dibahas dan disepakati dengan pengguna). Owner-level
 * setting, sama seperti Profil Perusahaan — mempengaruhi sidebar
 * SEMUA staf di perusahaan itu, bukan preferensi personal.
 *
 * Modul yang bisa di-toggle sengaja dibuat sebagai daftar tunggal
 * ($availableModules) supaya menambah modul baru (misal "Pembelian",
 * begitu halaman UI-nya ada) tinggal tambah 1 baris di sini — tidak
 * perlu migration baru, karena kolomnya sudah JSON sejak awal.
 */
#[Layout('layouts.app')]
class ModuleToggle extends Component
{
    public array $enabled = [];

    protected array $availableModules = [
        'budidaya' => [
            'label' => 'Budidaya',
            'description' => 'Menu Greenhouse dan Musim Tanam. Matikan kalau perusahaan Anda murni jual-beli (tengkulak) dan tidak menanam sendiri.',
        ],
    ];

    public function mount(): void
    {
        $this->authorize('update', auth()->user()->company);

        $company = auth()->user()->company;
        foreach (array_keys($this->availableModules) as $key) {
            $this->enabled[$key] = $company->hasModuleEnabled($key);
        }
    }

    public function save(): void
    {
        $company = auth()->user()->company;
        $this->authorize('update', $company);

        $company->update(['enabled_modules' => $this->enabled]);

        session()->flash('module-settings-saved', true);
    }

    public function render()
    {
        return view('livewire.settings.module-toggle', [
            'availableModules' => $this->availableModules,
        ]);
    }
}
