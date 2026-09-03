<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold">📈 Laporan</h1>
        <p class="lw-muted text-sm">Laporan keuangan inti perusahaan.</p>
    </div>

    <div class="flex gap-2 mb-6">
        <button wire:click="setTab('income-statement')" class="tab-btn px-4 py-2 {{ $activeTab === 'income-statement' ? 'active' : '' }}">Laba Rugi</button>
        <button wire:click="setTab('balance-sheet')" class="tab-btn px-4 py-2 {{ $activeTab === 'balance-sheet' ? 'active' : '' }}">Neraca</button>
        <button wire:click="setTab('trial-balance')" class="tab-btn px-4 py-2 {{ $activeTab === 'trial-balance' ? 'active' : '' }}">Neraca Saldo</button>
    </div>

    {{-- LABA RUGI --}}
    @if ($activeTab === 'income-statement' && $incomeStatement)
        <div class="glass rounded-2xl p-6 mb-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="lw-label">Dari Tanggal</label>
                    <input type="date" wire:model.live="from" class="lw-input px-3">
                </div>
                <div>
                    <label class="lw-label">Sampai Tanggal</label>
                    <input type="date" wire:model.live="to" class="lw-input px-3">
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl overflow-hidden mb-4">
            <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">Pendapatan</div>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($incomeStatement['revenue'] as $row)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                            <td class="px-4 py-2 lw-muted w-24">{{ $row['code'] }}</td>
                            <td class="px-4 py-2">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-4 text-center lw-muted">Belum ada pendapatan di periode ini.</td></tr>
                    @endforelse
                    <tr class="font-semibold" style="border-top:1px solid rgba(148,163,184,.15)">
                        <td class="px-4 py-2" colspan="2">Total Pendapatan</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($incomeStatement['total_revenue'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="glass rounded-2xl overflow-hidden mb-4">
            <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">Beban</div>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($incomeStatement['expenses'] as $row)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                            <td class="px-4 py-2 lw-muted w-24">{{ $row['code'] }}</td>
                            <td class="px-4 py-2">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-4 text-center lw-muted">Belum ada beban di periode ini.</td></tr>
                    @endforelse
                    <tr class="font-semibold" style="border-top:1px solid rgba(148,163,184,.15)">
                        <td class="px-4 py-2" colspan="2">Total Beban</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($incomeStatement['total_expenses'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="glass rounded-2xl p-4 flex justify-between items-center">
            <span class="font-bold">Laba/Rugi Bersih</span>
            <span class="font-bold text-lg" style="{{ $incomeStatement['net_income'] >= 0 ? 'color:#5ee878' : 'color:#fb7185' }}">
                Rp {{ number_format($incomeStatement['net_income'], 0, ',', '.') }}
            </span>
        </div>
    @endif

    {{-- NERACA --}}
    @if ($activeTab === 'balance-sheet' && $balanceSheet)
        <div class="glass rounded-2xl p-6 mb-4">
            <label class="lw-label">Per Tanggal</label>
            <input type="date" wire:model.live="asOf" class="lw-input px-3">
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <div class="glass rounded-2xl overflow-hidden mb-4">
                    <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">Aset</div>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($balanceSheet['assets'] as $row)
                                <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                                    <td class="px-4 py-2 lw-muted w-16">{{ $row['code'] }}</td>
                                    <td class="px-4 py-2">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-right">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold" style="border-top:1px solid rgba(148,163,184,.15)">
                                <td class="px-4 py-2" colspan="2">Total Aset</td>
                                <td class="px-4 py-2 text-right">Rp {{ number_format($balanceSheet['total_assets'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <div class="glass rounded-2xl overflow-hidden mb-4">
                    <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">Kewajiban</div>
                    <table class="w-full text-sm">
                        <tbody>
                            @forelse ($balanceSheet['liabilities'] as $row)
                                <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                                    <td class="px-4 py-2 lw-muted w-16">{{ $row['code'] }}</td>
                                    <td class="px-4 py-2">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-right">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-3 text-center lw-muted">Tidak ada kewajiban.</td></tr>
                            @endforelse
                            <tr class="font-semibold" style="border-top:1px solid rgba(148,163,184,.15)">
                                <td class="px-4 py-2" colspan="2">Total Kewajiban</td>
                                <td class="px-4 py-2 text-right">Rp {{ number_format($balanceSheet['total_liabilities'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="glass rounded-2xl overflow-hidden mb-4">
                    <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">Modal</div>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($balanceSheet['equity'] as $row)
                                <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                                    <td class="px-4 py-2 lw-muted w-16">{{ $row['code'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-right">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold" style="border-top:1px solid rgba(148,163,184,.15)">
                                <td class="px-4 py-2" colspan="2">Total Modal</td>
                                <td class="px-4 py-2 text-right">Rp {{ number_format($balanceSheet['total_equity'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl p-4 flex justify-between items-center">
            <span class="font-semibold text-sm">Aset = Kewajiban + Modal?</span>
            @if ($balanceSheet['is_balanced'])
                <span class="text-sm font-semibold" style="color:#5ee878">✓ Seimbang</span>
            @else
                <span class="text-sm font-semibold" style="color:#fb7185">⚠️ Tidak seimbang — hubungi dukungan teknis</span>
            @endif
        </div>
    @endif

    {{-- NERACA SALDO --}}
    @if ($activeTab === 'trial-balance' && $trialBalance)
        <div class="glass rounded-2xl overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama Akun</th>
                        <th class="px-4 py-3 text-right">Debit</th>
                        <th class="px-4 py-3 text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trialBalance['accounts'] as $row)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.06)">
                            <td class="px-4 py-2 lw-muted">{{ $row['code'] }}</td>
                            <td class="px-4 py-2">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['debit'] > 0 ? 'Rp '.number_format($row['debit'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['credit'] > 0 ? 'Rp '.number_format($row['credit'], 0, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center lw-muted">Belum ada aktivitas jurnal sama sekali.</td></tr>
                    @endforelse
                    <tr class="font-semibold" style="border-top:1px solid rgba(148,163,184,.15)">
                        <td class="px-4 py-2" colspan="2">Total</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($trialBalance['total_debit'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($trialBalance['total_credit'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="glass rounded-2xl p-4 flex justify-between items-center">
            <span class="font-semibold text-sm">Total Debit = Total Kredit?</span>
            @if ($trialBalance['is_balanced'])
                <span class="text-sm font-semibold" style="color:#5ee878">✓ Seimbang</span>
            @else
                <span class="text-sm font-semibold" style="color:#fb7185">⚠️ Tidak seimbang</span>
            @endif
        </div>
    @endif
</div>
