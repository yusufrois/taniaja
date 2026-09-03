<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">🌾 Musim Tanam</h1>
            <p class="lw-muted text-sm">Kelola siklus tanam per greenhouse.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Musim Tanam Baru</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Musim</th>
                        <th class="px-4 py-3">Greenhouse</th>
                        <th class="px-4 py-3">Komoditas</th>
                        <th class="px-4 py-3">HST</th>
                        <th class="px-4 py-3">Kelangsungan Hidup</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($seasons as $season)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 font-semibold">{{ $season->season_name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $season->greenhouse?->code }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $season->crop?->name }} — {{ $season->variety?->name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $season->hst !== null ? $season->hst.' hari' : '-' }}</td>
                            <td class="px-4 py-3 lw-muted">
                                {{ $season->survival_rate_percent !== null ? $season->survival_rate_percent.'%' : '-' }}
                                @if ($season->current_plant_count !== null)
                                    <span class="text-xs">({{ $season->current_plant_count }}/{{ $season->plant_count }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusLabel = ['planning' => 'Perencanaan', 'active' => 'Aktif', 'harvesting' => 'Panen', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$season->status] ?? $season->status;
                                    $statusColor = ['planning' => 'color:#fbbf24', 'active' => 'color:#5ee878', 'harvesting' => 'color:#38bdf8', 'completed' => 'color:#91a69a', 'cancelled' => 'color:#fb7185'][$season->status] ?? '';
                                @endphp
                                <span class="text-xs font-semibold" style="{{ $statusColor }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('web.seasons.detail', $season->id) }}" class="btn-secondary px-3 py-1.5 text-xs" style="color:#60a5fa">Detail</a>
                                @can('update', $season)
                                    <button wire:click="openEdit({{ $season->id }})" class="btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                @endcan
                                @can('delete', $season)
                                    <button wire:click="confirmDelete({{ $season->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center lw-muted">Belum ada musim tanam. Klik "+ Musim Tanam Baru" untuk mulai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($seasons->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $seasons->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-2xl p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">{{ $editingId ? 'Edit Musim Tanam' : 'Musim Tanam Baru' }}</h2>

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
                        <label class="lw-label">Nama Musim *</label>
                        <input type="text" wire:model="season_name" class="lw-input w-full px-3" placeholder="Musim 1 - Melon Golden">
                        @error('season_name') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="lw-label">Greenhouse *</label>
                            <select wire:model="greenhouse_id" class="lw-input w-full px-3">
                                <option value="">-- Pilih --</option>
                                @foreach ($greenhouses as $gh)
                                    <option value="{{ $gh->id }}">{{ $gh->code }} — {{ $gh->name }}</option>
                                @endforeach
                            </select>
                            @error('greenhouse_id') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="lw-label">Komoditas *</label>
                            <select wire:model.live="crop_id" class="lw-input w-full px-3">
                                <option value="">-- Pilih --</option>
                                @foreach ($crops as $crop)
                                    <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                                @endforeach
                            </select>
                            @error('crop_id') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="lw-label">Varietas *</label>
                            <select wire:model="variety_id" class="lw-input w-full px-3" @if(!$crop_id) disabled @endif>
                                <option value="">{{ $crop_id ? '-- Pilih --' : 'Pilih komoditas dulu' }}</option>
                                @foreach ($varieties as $variety)
                                    <option value="{{ $variety->id }}">{{ $variety->name }}</option>
                                @endforeach
                            </select>
                            @error('variety_id') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="lw-label">Tanggal Tanam *</label>
                            <input type="date" wire:model.live="planting_date" class="lw-input w-full px-3">
                            @if ($planting_date)
                                <p class="text-xs lw-muted mt-1">→ {{ \Carbon\Carbon::parse($planting_date)->translatedFormat('d F Y') }}</p>
                            @endif
                            @error('planting_date') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="lw-label">Estimasi Panen</label>
                            <input type="date" wire:model.live="estimated_harvest_date" class="lw-input w-full px-3">
                            @if ($estimated_harvest_date)
                                <p class="text-xs lw-muted mt-1">→ {{ \Carbon\Carbon::parse($estimated_harvest_date)->translatedFormat('d F Y') }}</p>
                            @endif
                            @error('estimated_harvest_date') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="lw-label">Tanggal Panen Aktual</label>
                            <input type="date" wire:model.live="actual_harvest_date" class="lw-input w-full px-3">
                            @if ($actual_harvest_date)
                                <p class="text-xs lw-muted mt-1">→ {{ \Carbon\Carbon::parse($actual_harvest_date)->translatedFormat('d F Y') }}</p>
                            @endif
                            @error('actual_harvest_date') <p class="lw-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="lw-label">Jumlah Tanaman</label>
                            <input type="number" wire:model="plant_count" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Target Hasil (kg)</label>
                            <input type="number" step="0.01" wire:model="target_yield" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Status *</label>
                            <select wire:model="status" class="lw-input w-full px-3">
                                <option value="planning">Perencanaan</option>
                                <option value="active" disabled>Aktif (otomatis saat tanggal tanam tiba)</option>
                                <option value="harvesting" disabled>Panen (otomatis saat isi tanggal panen aktual)</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                            <p class="text-xs lw-muted mt-1">Aktif & Panen berjalan otomatis — pilih "Selesai" sendiri kalau musim ini sudah benar-benar tuntas.</p>
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

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <p class="mb-4">Yakin ingin menghapus musim tanam ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
