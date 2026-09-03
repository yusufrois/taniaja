<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">📒 Bagan Akun</h1>
            <p class="lw-muted text-sm">Daftar akun Kas/Bank/Hutang/Modal untuk pencatatan keuangan.</p>
        </div>
        @if ($canCreate)
            <div class="flex gap-2">
                <button wire:click="seedDefaults" class="btn-secondary px-4 py-2 text-sm">Isi Akun Standar</button>
                @if ($hasAnyAccounts)
                    <button wire:click="backfillOldTransactions" class="btn-secondary px-4 py-2 text-sm">Posting Ulang Transaksi Lama</button>
                @endif
                <button wire:click="openCreate" class="btn-primary px-4 py-2 text-sm">+ Akun Kustom</button>
            </div>
        @endif
    </div>

    @if ($showBackfillResult)
        <div class="glass rounded-2xl p-4 mb-6 text-sm">
            <p class="font-semibold mb-2">Hasil Posting Ulang:</p>
            @php
                $labels = [
                    'expense' => 'Beban', 'debt' => 'Hutang', 'debt_payment' => 'Cicilan Hutang',
                    'capital_transaction' => 'Transaksi Modal', 'purchase' => 'Pembelian',
                    'purchase_payment' => 'Pembayaran Pembelian', 'sale' => 'Penjualan',
                    'sale_payment' => 'Pembayaran Penjualan', 'stock_batch_sale' => 'Jual Cepat',
                    'account_transfer' => 'Transfer Antar Akun', 'asset' => 'Aset Tetap',
                ];
                $totalPosted = array_sum($backfillResult);
            @endphp
            @if ($totalPosted === 0)
                <p class="lw-muted">Tidak ada transaksi lama yang perlu di-posting ulang — semuanya sudah punya jurnal.</p>
            @else
                <ul class="space-y-1 lw-muted">
                    @foreach ($backfillResult as $type => $count)
                        @if ($count > 0)
                            <li>{{ $labels[$type] ?? $type }}: <span style="color:#5ee878">{{ $count }} transaksi</span></li>
                        @endif
                    @endforeach
                </ul>
                <p class="mt-2 text-xs lw-muted">Silakan cek halaman Laporan lagi — datanya seharusnya sudah muncul sekarang.</p>
            @endif
        </div>
    @endif
    @if (! $hasAnyAccounts)
        <div class="glass rounded-2xl p-6 mb-6 text-center">
            <p class="mb-3">Belum ada akun sama sekali. Semua dropdown "Dibayar dari Akun" di halaman Beban/Modal/Hutang akan kosong sampai ini diisi.</p>
            @if ($canCreate)
                <button wire:click="seedDefaults" class="btn-primary px-4 py-2 text-sm">Isi 16 Akun Standar Sekarang</button>
            @endif
        </div>
    @endif

    @php
        $typeLabels = ['asset' => '🏦 Aset', 'liability' => '💳 Kewajiban', 'equity' => '🏛️ Modal', 'revenue' => '📈 Pendapatan', 'expense' => '💸 Beban'];
    @endphp

    @foreach ($typeLabels as $type => $label)
        @if (isset($accountsByType[$type]))
            <div class="glass rounded-2xl overflow-hidden mb-4">
                <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">{{ $label }}</div>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($accountsByType[$type] as $account)
                            <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                                <td class="px-4 py-2 lw-muted w-24">{{ $account->code }}</td>
                                <td class="px-4 py-2">{{ $account->name }}</td>
                                @if ($canViewCost)
                                    <td class="px-4 py-2 text-right">Rp {{ number_format($account->balance(), 0, ',', '.') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    @if ($showModal)
        <div class="fixed inset-0 overflow-y-auto px-4" style="z-index:100; background-color: rgba(0,0,0,0.88)" wire:click.self="closeModal">
            <div class="mx-auto my-8 w-full max-w-sm p-6" style="background: rgba(8,20,14,0.98); border: 1px solid rgba(74,222,128,.15); border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,.35);">
                <h2 class="text-lg font-bold mb-4">Tambah Akun Kustom</h2>

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
                        <label class="lw-label">Kode Akun *</label>
                        <input type="text" wire:model="code" class="lw-input w-full px-3" placeholder="1120">
                    </div>
                    <div>
                        <label class="lw-label">Nama Akun *</label>
                        <input type="text" wire:model="name" class="lw-input w-full px-3" placeholder="Bank BCA">
                    </div>
                    <div>
                        <label class="lw-label">Tipe *</label>
                        <select wire:model="type" class="lw-input w-full px-3">
                            <option value="asset">Aset</option>
                            <option value="liability">Kewajiban</option>
                            <option value="equity">Modal</option>
                            <option value="revenue">Pendapatan</option>
                            <option value="expense">Beban</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
