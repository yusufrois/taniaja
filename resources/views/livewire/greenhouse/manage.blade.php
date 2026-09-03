<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">🏡 Greenhouse</h1>
            <p class="lw-muted text-sm">Kelola daftar greenhouse perusahaan Anda.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Tambah Greenhouse</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3">Luas (m²)</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($greenhouses as $gh)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 font-semibold">{{ $gh->code }}</td>
                            <td class="px-4 py-3">{{ $gh->name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $gh->location ?: '-' }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $gh->area ? number_format($gh->area, 1) : '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusLabel = ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'under_construction' => 'Konstruksi'][$gh->status] ?? $gh->status;
                                    $statusColor = ['active' => 'color:#5ee878', 'inactive' => 'color:#91a69a', 'under_construction' => 'color:#fbbf24'][$gh->status] ?? '';
                                @endphp
                                <span class="text-xs font-semibold" style="{{ $statusColor }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @can('update', $gh)
                                    <button wire:click="openEdit({{ $gh->id }})" class="btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                @endcan
                                @can('delete', $gh)
                                    <button wire:click="confirmDelete({{ $gh->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center lw-muted">
                                Belum ada data greenhouse. Klik "+ Tambah Greenhouse" untuk mulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($greenhouses->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">
                {{ $greenhouses->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-lg p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">{{ $editingId ? 'Edit Greenhouse' : 'Tambah Greenhouse' }}</h2>

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
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Kode *</label>
                            <input type="text" wire:model="code" class="lw-input w-full px-3" placeholder="GH-01">
                            @error('code') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="lw-label">Status *</label>
                            <select wire:model="status" class="lw-input w-full px-3">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="under_construction">Konstruksi</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Nama *</label>
                        <input type="text" wire:model="name" class="lw-input w-full px-3" placeholder="Greenhouse Melon A">
                        @error('name') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="lw-label">Lokasi</label>
                        <input type="text" wire:model="location" class="lw-input w-full px-3" placeholder="Blitar, Jawa Timur">
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="lw-label">Panjang (m)</label>
                            <input type="number" step="0.01" wire:model.live="length" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Lebar (m)</label>
                            <input type="number" step="0.01" wire:model.live="width" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Luas (m²)</label>
                            <div class="lw-input w-full px-3 flex items-center" style="opacity:.7">
                                {{ $area !== null ? number_format($area, 2) : '-' }}
                            </div>
                            <p class="text-xs lw-muted mt-1">Otomatis, Panjang × Lebar</p>
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Catatan</label>
                        <textarea wire:model="notes" rows="2" class="lw-input w-full px-3 py-2"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete confirmation --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <p class="mb-4">Yakin ingin menghapus greenhouse ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
