<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold">🧩 Pengaturan Modul</h1>
        <p class="lw-muted text-sm">Sesuaikan menu yang tampil untuk seluruh staf, sesuai jenis usaha Anda.</p>
    </div>

    @if (session('module-settings-saved'))
        <div class="rounded-xl px-4 py-3 mb-4 text-sm" style="background:rgba(94,232,120,.12); border:1px solid rgba(94,232,120,.35)">
            <span style="color:#5ee878">✓ Pengaturan tersimpan.</span> Sidebar akan menyesuaikan setelah Anda muat ulang halaman.
        </div>
    @endif

    <div class="glass rounded-2xl overflow-hidden">
        @foreach ($availableModules as $key => $module)
            <div class="flex items-center justify-between {{ !$loop->last ? 'border-b' : '' }}" style="padding:16px 20px; border-color: rgba(148,163,184,.1)">
                <div class="pr-4">
                    <p class="font-semibold">{{ $module['label'] }}</p>
                    <p class="text-xs lw-muted mt-1">{{ $module['description'] }}</p>
                </div>
                <button
                    type="button"
                    wire:click="$set('enabled.{{ $key }}', {{ $enabled[$key] ? 'false' : 'true' }})"
                    style="flex-shrink:0; position:relative; display:inline-flex; align-items:center; height:24px; width:44px; border-radius:9999px; border:none; cursor:pointer; transition:background-color .15s; background: {{ $enabled[$key] ? '#5ee878' : 'rgba(148,163,184,.25)' }}"
                >
                    <span style="display:block; height:16px; width:16px; border-radius:9999px; background:#fff; transition:transform .15s; transform: translateX({{ $enabled[$key] ? '22px' : '2px' }})"></span>
                </button>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end mt-4">
        <button wire:click="save" class="btn-primary px-4 py-2 text-sm">Simpan Pengaturan</button>
    </div>

    <p class="text-xs lw-muted mt-4">
        Catatan: mematikan modul cuma menyembunyikan menunya dari sidebar — data yang sudah ada TIDAK terhapus, dan bisa dinyalakan lagi kapan saja.
    </p>
</div>
