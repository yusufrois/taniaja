<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">🗂️ Kategori Aset</h1>
            <p class="lw-muted text-sm">Kelola daftar kategori untuk Aset Tetap.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Tambah Kategori</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 font-semibold">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @can('update', $category)
                                    <button wire:click="openEdit({{ $category->id }})" class="btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                @endcan
                                @can('delete', $category)
                                    <button wire:click="confirmDelete({{ $category->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-10 text-center lw-muted">Belum ada kategori aset. Klik "+ Tambah Kategori" untuk mulai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $categories->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-sm p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori Aset' }}</h2>

                @if ($errors->any())
                    <div class="rounded-xl px-4 py-3 mb-4 text-sm" style="background:rgba(251,113,133,.12); border:1px solid rgba(251,113,133,.35)">
                        <p class="font-semibold mb-1" style="color:#fb7185">⚠️ Ada yang perlu diperbaiki:</p>
                        <ul class="list-disc list-inside space-y-0.5" style="color:#fecdd3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="lw-label">Nama Kategori *</label>
                        <input type="text" wire:model="name" class="lw-input w-full px-3" placeholder="Peralatan">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                @if ($deleteError)
                    <p class="text-xs mb-3" style="color:#fb7185">⚠️ {{ $deleteError }}</p>
                @endif
                <p class="mb-4">Yakin ingin menghapus kategori ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null); $set('deleteError', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
