<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">🌾 Panen</h1>
            <p class="lw-muted text-sm">Catat hasil panen — otomatis jadi stok yang bisa dijual.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Catat Panen</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Musim</th>
                        <th class="px-4 py-3">Greenhouse</th>
                        <th class="px-4 py-3">Total Berat</th>
                        <th class="px-4 py-3">Grade</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($harvests as $harvest)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 lw-muted">{{ $harvest->harvest_date?->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $harvest->season?->season_name ?? '-' }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $harvest->greenhouse?->code }}</td>
                            <td class="px-4 py-3 font-semibold">{{ number_format($harvest->totalWeight(), 1) }} kg</td>
                            <td class="px-4 py-3 lw-muted">{{ $harvest->items->pluck('grade.name')->filter()->implode(', ') }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @can('delete', $harvest)
                                    <button wire:click="confirmDelete({{ $harvest->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center lw-muted">Belum ada catatan panen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($harvests->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $harvests->links() }}</div>
        @endif
    </div>

    {{-- Catat Panen --}}
    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-2xl p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">Catat Panen</h2>

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
                            <label class="lw-label">Musim Tanam *</label>
                            <select wire:model="season_id" class="lw-input w-full px-3">
                                <option value="">-- Pilih --</option>
                                @foreach ($seasons as $season)
                                    <option value="{{ $season->id }}">{{ $season->greenhouse?->code }} — {{ $season->season_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="lw-label">Tanggal Panen *</label>
                            <input type="date" wire:model="harvest_date" class="lw-input w-full px-3">
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Catatan (opsional)</label>
                        <input type="text" wire:model="notes" class="lw-input w-full px-3">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="lw-label !mb-0">Hasil Panen per Grade *</label>
                            <button type="button" wire:click="addItem" class="text-xs" style="color:#5ee878">+ Tambah Grade</button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($items as $index => $item)
                                <div class="flex gap-2 items-start p-3 rounded-xl" style="background:rgba(255,255,255,.03)">
                                    <div class="flex-1 grid grid-cols-3 gap-2">
                                        <select wire:model="items.{{ $index }}.grade_id" class="lw-input px-2 text-sm">
                                            <option value="">Grade</option>
                                            @foreach ($grades as $grade)
                                                <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" step="0.01" wire:model="items.{{ $index }}.weight" placeholder="Berat (kg)" class="lw-input px-2 text-sm">
                                        <input type="number" step="0.01" wire:model="items.{{ $index }}.quantity" placeholder="Jumlah buah (opsional)" class="lw-input px-2 text-sm">
                                    </div>
                                    <button type="button" wire:click="removeItem({{ $index }})" class="shrink-0 text-xs mt-2" style="color:#fb7185">✕</button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Simpan Panen</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Konfirmasi Hapus --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <p class="mb-4">Yakin ingin menghapus catatan panen ini? Stok yang sudah terbentuk dari panen ini juga ikut terhapus.</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
