<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">🏦 Modal</h1>
            <p class="lw-muted text-sm">Catatan setoran modal dan penarikan (prive).</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Catat Transaksi Modal</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Sumber</th>
                        @if ($canViewCost)
                            <th class="px-4 py-3">Jumlah</th>
                        @endif
                        <th class="px-4 py-3">Dicatat oleh</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 lw-muted">{{ $transaction->date?->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $typeLabel = ['owner_investment' => 'Setoran Pemilik', 'investor_investment' => 'Setoran Investor', 'withdrawal' => 'Penarikan (Prive)'][$transaction->type] ?? $transaction->type;
                                    $typeColor = $transaction->type === 'withdrawal' ? 'color:#fbbf24' : 'color:#5ee878';
                                @endphp
                                <span class="text-xs font-semibold" style="{{ $typeColor }}">{{ $typeLabel }}</span>
                            </td>
                            <td class="px-4 py-3 lw-muted">{{ $transaction->source ?: '-' }}</td>
                            @if ($canViewCost)
                                <td class="px-4 py-3 font-semibold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            @endif
                            <td class="px-4 py-3 lw-muted">{{ $transaction->creator?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $transaction)
                                    <button wire:click="confirmDelete({{ $transaction->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center lw-muted">Belum ada transaksi modal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $transactions->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-lg p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">Catat Transaksi Modal</h2>

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
                        <label class="lw-label">Jenis Transaksi *</label>
                        <select wire:model="type" class="lw-input w-full px-3">
                            <option value="owner_investment">Setoran Modal Pemilik</option>
                            <option value="investor_investment">Setoran Modal Investor</option>
                            <option value="withdrawal">Penarikan (Prive)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Tanggal *</label>
                            <input type="date" wire:model="date" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Jumlah (Rp) *</label>
                            <input type="number" step="0.01" wire:model="amount" class="lw-input w-full px-3">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Sumber (opsional)</label>
                            <input type="text" wire:model="source" class="lw-input w-full px-3" placeholder="Nama pemilik/investor">
                        </div>
                        <div>
                            <label class="lw-label">Akun Kas/Bank (opsional)</label>
                            <select wire:model="chart_of_account_id" class="lw-input w-full px-3">
                                <option value="">-- Kas default --</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Metode</label>
                        <select wire:model="payment_method" class="lw-input w-full px-3">
                            <option value="">-- Pilih --</option>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Giro">Giro</option>
                            <option value="QRIS">QRIS</option>
                        </select>
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
                <p class="mb-4">Yakin ingin menghapus transaksi modal ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
