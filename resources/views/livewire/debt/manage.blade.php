<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">💳 Hutang</h1>
            <p class="lw-muted text-sm">Kelola pinjaman dan cicilan pembayarannya.</p>
        </div>
        @if ($canCreate)
            <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Catat Hutang Baru</button>
        @endif
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Kreditur</th>
                        <th class="px-4 py-3">Cicilan</th>
                        <th class="px-4 py-3">Jatuh Tempo</th>
                        @if ($canViewCost)
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Sisa</th>
                        @endif
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($debts as $debt)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 font-semibold">{{ $debt->creditor_name }}</td>
                            <td class="px-4 py-3 lw-muted">
                                @if ($debt->installment_months)
                                    <span class="text-xs">{{ $debt->installments_paid }}/{{ $debt->installment_months }} bln</span>
                                @else
                                    <span class="text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 lw-muted">{{ $debt->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                            @if ($canViewCost)
                                <td class="px-4 py-3">Rp {{ number_format($debt->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 font-semibold">Rp {{ number_format($debt->remaining, 0, ',', '.') }}</td>
                            @endif
                            <td class="px-4 py-3">
                                @php
                                    $statusLabel = ['unpaid' => 'Belum Bayar', 'partial' => 'Sebagian', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo'][$debt->status] ?? $debt->status;
                                    $statusColor = ['unpaid' => 'color:#91a69a', 'partial' => 'color:#fbbf24', 'paid' => 'color:#5ee878', 'overdue' => 'color:#fb7185'][$debt->status] ?? '';
                                @endphp
                                <span class="text-xs font-semibold" style="{{ $statusColor }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if ($debt->status !== 'paid')
                                    @can('update', $debt)
                                        <button wire:click="openPayment({{ $debt->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#5ee878">Bayar</button>
                                    @endcan
                                @endif
                                <button wire:click="openDetail({{ $debt->id }})" class="btn-secondary px-3 py-1.5 text-xs">Detail</button>
                                @can('delete', $debt)
                                    <button wire:click="confirmDelete({{ $debt->id }})" class="btn-secondary px-3 py-1.5 text-xs" style="color:#fb7185">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center lw-muted">Belum ada catatan hutang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($debts->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid rgba(148,163,184,.08)">{{ $debts->links() }}</div>
        @endif
    </div>

    {{-- Create Debt modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-lg p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">Catat Hutang Baru</h2>

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
                        <label class="lw-label">Nama Kreditur *</label>
                        <input type="text" wire:model="creditor_name" class="lw-input w-full px-3" placeholder="Bank BRI / Koperasi Tani">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Tanggal Pinjam *</label>
                            <input type="date" wire:model="debt_date" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Jatuh Tempo</label>
                            <input type="date" wire:model="due_date" class="lw-input w-full px-3">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="lw-label">Jumlah Pinjaman (Rp) *</label>
                            <input type="number" step="0.01" wire:model="amount" class="lw-input w-full px-3">
                        </div>
                        <div>
                            <label class="lw-label">Jangka Waktu (bulan, opsional)</label>
                            <input type="number" wire:model="installment_months" class="lw-input w-full px-3" placeholder="24">
                            <p class="text-xs lw-muted mt-1">Kosongkan kalau bukan pinjaman cicilan bulanan.</p>
                        </div>
                    </div>

                    <div>
                        <label class="lw-label">Masuk ke Akun (opsional)</label>
                        <select wire:model="chart_of_account_id" class="lw-input w-full px-3">
                            <option value="">-- Kas default --</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
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

    {{-- Record Payment modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closePaymentModal">
            <div class="mx-auto my-8 w-full max-w-md p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">Catat Pembayaran Cicilan</h2>

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

                <form wire:submit="savePayment" class="space-y-4">
                    <div>
                        <label class="lw-label">Tanggal Bayar *</label>
                        <input type="date" wire:model="payment_date" class="lw-input w-full px-3">
                    </div>
                    <div>
                        <label class="lw-label">Jumlah Bayar (Rp) *</label>
                        <input type="number" step="0.01" wire:model="payment_amount" class="lw-input w-full px-3">
                    </div>
                    <div>
                        <label class="lw-label">Dibayar dari Akun (opsional)</label>
                        <select wire:model="payment_chart_of_account_id" class="lw-input w-full px-3">
                            <option value="">-- Kas default --</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
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
                        <textarea wire:model="payment_notes" rows="2" class="lw-input w-full px-3 py-2"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closePaymentModal" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Detail: jadwal cicilan + riwayat pembayaran --}}
    @if ($showDetailModal && $viewingDebt)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeDetailModal">
            <div class="mx-auto my-8 w-full max-w-2xl p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-1">{{ $viewingDebt->creditor_name }}</h2>
                <p class="lw-muted text-xs mb-4">
                    @if ($canViewCost)
                        Total Rp {{ number_format($viewingDebt->amount, 0, ',', '.') }} —
                        Sisa Rp {{ number_format($viewingDebt->remaining, 0, ',', '.') }}
                    @endif
                </p>

                @if ($viewingDebt->installment_months)
                    <h3 class="text-sm font-semibold mb-2">Jadwal Cicilan ({{ $viewingDebt->installments_paid }}/{{ $viewingDebt->installment_months }} bulan lunas)</h3>
                    <div class="overflow-x-auto mb-6" style="max-height:220px; overflow-y:auto; border:1px solid rgba(148,163,184,.1); border-radius:8px;">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="lw-muted text-left" style="border-bottom:1px solid rgba(148,163,184,.15)">
                                    <th class="px-3 py-2">Bulan Ke</th>
                                    <th class="px-3 py-2">Jatuh Tempo</th>
                                    @if ($canViewCost)
                                        <th class="px-3 py-2">Jumlah</th>
                                    @endif
                                    <th class="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($viewingDebt->schedule() as $row)
                                    <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                                        <td class="px-3 py-2">{{ $row['installment_no'] }}</td>
                                        <td class="px-3 py-2 lw-muted">{{ $row['due_date']->translatedFormat('d M Y') }}</td>
                                        @if ($canViewCost)
                                            <td class="px-3 py-2">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                        @endif
                                        <td class="px-3 py-2">
                                            @if ($row['is_paid'])
                                                <span style="color:#5ee878">✓ Lunas</span>
                                            @else
                                                <span class="lw-muted">Belum</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h3 class="text-sm font-semibold mb-2">Riwayat Pembayaran</h3>
                <div class="overflow-x-auto" style="max-height:180px; overflow-y:auto; border:1px solid rgba(148,163,184,.1); border-radius:8px;">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="lw-muted text-left" style="border-bottom:1px solid rgba(148,163,184,.15)">
                                <th class="px-3 py-2">Tanggal</th>
                                @if ($canViewCost)
                                    <th class="px-3 py-2">Jumlah</th>
                                @endif
                                <th class="px-3 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($viewingDebt->payments as $payment)
                                <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                                    <td class="px-3 py-2 lw-muted">{{ $payment->payment_date?->translatedFormat('d M Y') }}</td>
                                    @if ($canViewCost)
                                        <td class="px-3 py-2">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    @endif
                                    <td class="px-3 py-2 lw-muted">{{ $payment->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-6 text-center lw-muted">Belum ada pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-4">
                    <button wire:click="closeDetailModal" class="btn-secondary px-4 py-2 text-sm">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="mx-auto my-8 w-full max-w-sm p-6 text-center" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <p class="mb-4">Yakin ingin menghapus catatan hutang ini?</p>
                <div class="flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                    <button wire:click="delete" class="btn-primary px-4 py-2 text-sm" style="background:linear-gradient(135deg,#fb7185,#f43f5e)">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
