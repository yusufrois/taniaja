<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">🏗️ Aset Tetap</h1>
            <p class="lw-muted text-sm">Kelola daftar aset tetap perusahaan (bangunan, peralatan, dst).</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Tambah Aset</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Greenhouse</th>
                        <th class="px-4 py-3">Tgl Beli</th>
                        @if ($canViewCost)
                            <th class="px-4 py-3">Nilai</th>
                        @endif
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 font-semibold">{{ $asset->name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $asset->category }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $asset->greenhouse?->code ?? '-' }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $asset->purchase_date?->translatedFormat('d M Y') }}</td>
                            @if ($canViewCost)
                                <td class="px-4 py-3">Rp {{ number_format($asset->value, 0, ',', '.') }}</td>
                            @endif
                            <td class="px-4 py-3">
                                @php
                                    $statusLabel = ['active' => 'Aktif', 'disposed' => 'Dilepas', 'under_maintenance' => 'Perawatan'][$asset->status] ?? $asset->status;
                                    $statusColor = ['active' => 'color:#5ee878', 'disposed' => 'color:#91a69a', 'under_maintenance' => 'color:#fbbf24'][$asset->status] ?? '';
                                @endphp
                                <span class="text-xs font-semibold" style="{{ $statusColor }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @can('update', $asset)
                                    <button wire:click="openEdit({{ $asset->id }})" class="btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                @endcan
                                @can('delete', $asset)
                                    <button wire:click="confirmDelete({{ $asset->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center lw-muted">Belum ada data aset tetap.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assets->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $assets->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-lg p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">{{ $editingId ? 'Edit Aset' : 'Tambah Aset Tetap' }}</h2>

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
                            <label class="lw-label">Nama Aset *</label>
                            <input type="text" wire:model="name" class="lw-input w-full px-3" placeholder="Pompa Air Sumur Bor">
                        </div>
                        <div>
                            <label class="lw-label">Kategori *</label>
                            <select wire:model="category" class="lw-input w-full px-3">
                                <option value="">-- Pilih --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Greenhouse (opsional)</label>
                        <select wire:model="greenhouse_id" class="lw-input w-full px-3">
                            <option value="">-- Tidak terkait --</option>
                            @foreach ($greenhouses as $gh)
                                <option value="{{ $gh->id }}">{{ $gh->code }} — {{ $gh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Tanggal Beli *</label>
                            <input type="date" wire:model="purchase_date" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Nilai (Rp) *</label>
                            <input type="number" step="0.01" wire:model="value" class="lw-input w-full px-3">
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Dibayar dari Akun (opsional)</label>
                        <select wire:model="chart_of_account_id" class="lw-input w-full px-3">
                            <option value="">-- Kas default --</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs lw-muted mt-1">Otomatis dicatat sebagai pengeluaran Aset Tetap dan mengurangi saldo akun ini.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Umur Ekonomis (tahun)</label>
                            <input type="number" wire:model="useful_life_years" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Status *</label>
                            <select wire:model="status" class="lw-input w-full px-3">
                                <option value="active">Aktif</option>
                                <option value="under_maintenance">Perawatan</option>
                                <option value="disposed">Dilepas</option>
                            </select>
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
                <p class="mb-4">Yakin ingin menghapus aset ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
