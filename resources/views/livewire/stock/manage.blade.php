<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">📦 Stok / Gudang</h1>
            <p class="lw-muted text-sm">Stok panen sendiri dan hasil beli, berdampingan.</p>
        </div>
    </div>

    <div class="flex gap-2 mb-4">
        <button wire:click="$set('sourceFilter', '')" class="btn-secondary px-3 py-1.5 text-xs" style="{{ $sourceFilter === '' ? 'background:rgba(94,232,120,.15); border-color:#5ee878' : '' }}">Semua</button>
        <button wire:click="$set('sourceFilter', 'own_harvest')" class="btn-secondary px-3 py-1.5 text-xs" style="{{ $sourceFilter === 'own_harvest' ? 'background:rgba(94,232,120,.15); border-color:#5ee878' : '' }}">🌾 Panen Sendiri</button>
        <button wire:click="$set('sourceFilter', 'purchased')" class="btn-secondary px-3 py-1.5 text-xs" style="{{ $sourceFilter === 'purchased' ? 'background:rgba(94,232,120,.15); border-color:#5ee878' : '' }}">🛒 Hasil Beli</button>
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3">Komoditas</th>
                        <th class="px-4 py-3">Grade</th>
                        <th class="px-4 py-3">Stok Tersedia</th>
                        @if ($canViewCost)
                            <th class="px-4 py-3">Harga Pokok</th>
                        @endif
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 lw-muted">{{ $batch->acquired_date?->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($batch->source_type === 'own_harvest')
                                    <span class="text-xs font-semibold" style="color:#5ee878">🌾 Panen Sendiri</span>
                                @else
                                    <span class="text-xs font-semibold" style="color:#60a5fa">🛒 Hasil Beli</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $batch->crop?->name }} — {{ $batch->variety?->name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $batch->grade?->name ?? '-' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ number_format($batch->quantity_available, 1) }} kg</td>
                            @if ($canViewCost)
                                <td class="px-4 py-3 lw-muted">Rp {{ number_format($batch->unit_cost, 0, ',', '.') }}/kg</td>
                            @endif
                            <td class="px-4 py-3 text-right">
                                @if ($canSell && $batch->quantity_available > 0)
                                    <button wire:click="openSell({{ $batch->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#5ee878">Jual Cepat</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canViewCost ? 7 : 6 }}" class="px-4 py-10 text-center lw-muted">Belum ada stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($batches->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $batches->links() }}</div>
        @endif
    </div>

    {{-- Jual Cepat --}}
    @if ($sellingBatchId && $sellingBatch)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeSell">
            <div class="mx-auto my-8 w-full max-w-md p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-1">Jual Cepat</h2>
                <p class="lw-muted text-xs mb-4">
                    {{ $sellingBatch->crop?->name }} — {{ $sellingBatch->variety?->name }}
                    ({{ $sellingBatch->grade?->name }}) · Tersedia {{ number_format($sellingBatch->quantity_available, 1) }} kg
                </p>

                @if ($errors->any())
                    <div class="rounded-xl px-4 py-3 mb-4 text-sm" style="background:rgba(251,113,133,.12); border:1px solid rgba(251,113,133,.35)">
                        <ul class="list-disc list-inside space-y-0.5" style="color:#fecdd3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit="sell" class="space-y-4">
                    <div>
                        <label class="lw-label">Pelanggan (opsional)</label>
                        <select wire:model="customer_id" class="lw-input w-full px-3">
                            <option value="">-- Umum / Tanpa Nama --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Jumlah Terjual (kg) *</label>
                            <input type="number" step="0.01" wire:model="quantity_sold" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Harga per kg *</label>
                            <input type="number" step="0.01" wire:model="sale_price_per_unit" class="lw-input w-full px-3">
                        </div>
                    </div>
                    <div>
                        <label class="lw-label">Catatan (opsional)</label>
                        <input type="text" wire:model="notes" class="lw-input w-full px-3">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeSell" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Jual</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
