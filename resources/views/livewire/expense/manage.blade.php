<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">💸 Beban Operasional</h1>
            <p class="lw-muted text-sm">Kelola pencatatan pengeluaran perusahaan.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Catat Beban</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Greenhouse</th>
                        <th class="px-4 py-3">Musim</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        @if ($canViewCost)
                            <th class="px-4 py-3">Jumlah</th>
                        @endif
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 lw-muted">{{ $expense->date?->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $expense->category?->name }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $expense->greenhouse?->code ?? '-' }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $expense->season?->season_name ?? '-' }}</td>
                            <td class="px-4 py-3 lw-muted">{{ $expense->description ?: '-' }}</td>
                            @if ($canViewCost)
                                <td class="px-4 py-3">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                            @endif
                            <td class="px-4 py-3">
                                @if ($expense->approved_at)
                                    <span class="text-xs font-semibold" style="color:#5ee878">✓ Disetujui</span>
                                @else
                                    <span class="text-xs font-semibold" style="color:#fbbf24">Menunggu</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if (! $expense->approved_at && $canApprove)
                                    <button wire:click="approve({{ $expense->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#5ee878">Setujui</button>
                                @endif
                                @can('update', $expense)
                                    <button wire:click="openEdit({{ $expense->id }})" class="btn-secondary px-3 py-1.5 text-xs">Edit</button>
                                @endcan
                                @can('delete', $expense)
                                    <button wire:click="confirmDelete({{ $expense->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center lw-muted">Belum ada catatan beban.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $expenses->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-2xl p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">{{ $editingId ? 'Edit Beban' : 'Catat Beban Baru' }}</h2>

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
                            <label class="lw-label">Tanggal *</label>
                            <input type="date" wire:model="date" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Kategori *</label>
                            <select wire:model="expense_category_id" class="lw-input w-full px-3">
                                <option value="">-- Pilih --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Greenhouse (opsional)</label>
                            <select wire:model.live="greenhouse_id" class="lw-input w-full px-3">
                                <option value="">-- Tidak terkait greenhouse --</option>
                                @foreach ($greenhouses as $gh)
                                    <option value="{{ $gh->id }}">{{ $gh->code }} — {{ $gh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="lw-label">Musim Tanam (opsional)</label>
                            <select wire:model="season_id" class="lw-input w-full px-3">
                                <option value="">
                                    {{ $greenhouse_id ? '-- Tidak terkait musim --' : '-- Semua musim --' }}
                                </option>
                                @foreach ($seasons as $season)
                                    <option value="{{ $season->id }}">{{ $season->season_name }}</option>
                                @endforeach
                            </select>
                            @if ($greenhouse_id && $seasons->isEmpty())
                                <p class="text-xs lw-muted mt-1">Greenhouse ini belum punya musim tanam aktif.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Jumlah (Rp) *</label>
                            <input type="number" step="0.01" wire:model="amount" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Dibayar dari Akun (opsional)</label>
                            <select wire:model="chart_of_account_id" class="lw-input w-full px-3">
                                <option value="">-- Kas default --</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Metode Pembayaran</label>
                        <select wire:model="payment_method" class="lw-input w-full px-3">
                            <option value="">-- Pilih --</option>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Giro">Giro</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="lw-label">Deskripsi</label>
                        <textarea wire:model="description" rows="2" class="lw-input w-full px-3 py-2"></textarea>
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
                <p class="mb-4">Yakin ingin menghapus catatan beban ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
