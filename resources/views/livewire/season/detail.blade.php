<div>
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('web.seasons') }}" class="btn-secondary px-3 py-1.5 text-sm">← Kembali</a>
        <h1 class="text-xl font-bold">{{ $season->season_name }}</h1>
        @php
            $statusLabel = ['planning' => 'Perencanaan', 'active' => 'Aktif', 'harvesting' => 'Panen', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$season->status] ?? $season->status;
            $statusColor = ['planning' => 'color:#91a69a', 'active' => 'color:#5ee878', 'harvesting' => 'color:#fbbf24', 'completed' => 'color:#60a5fa', 'cancelled' => 'color:#fb7185'][$season->status] ?? '';
        @endphp
        <span class="text-xs font-semibold px-2 py-1 rounded-full" style="{{ $statusColor }}; background:rgba(255,255,255,.06)">{{ $statusLabel }}</span>
    </div>
    <p class="lw-muted text-sm mb-6">
        {{ $season->greenhouse?->code }} — {{ $season->greenhouse?->name }} ·
        {{ $season->crop?->name }} ({{ $season->variety?->name }}) ·
        Tanam {{ $season->planting_date?->translatedFormat('d F Y') }}
    </p>

    {{-- Kartu ringkasan laba rugi --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @if ($canViewCost)
            <div class="glass rounded-2xl p-4">
                <p class="text-xs lw-muted mb-1">Pendapatan</p>
                <p class="text-lg font-bold" style="color:#5ee878">Rp {{ number_format($profitLoss['revenue'], 0, ',', '.') }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-xs lw-muted mb-1">Biaya Produksi</p>
                <p class="text-lg font-bold" style="color:#fbbf24">Rp {{ number_format($profitLoss['production_cost_total'], 0, ',', '.') }}</p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-xs lw-muted mb-1">Laba/Rugi Bersih</p>
                <p class="text-lg font-bold" style="{{ $profitLoss['net_profit'] >= 0 ? 'color:#5ee878' : 'color:#fb7185' }}">
                    Rp {{ number_format($profitLoss['net_profit'], 0, ',', '.') }}
                </p>
            </div>
            <div class="glass rounded-2xl p-4">
                <p class="text-xs lw-muted mb-1">HPP / kg</p>
                <p class="text-lg font-bold">{{ $hpp['hpp_per_kg'] ? 'Rp '.number_format($hpp['hpp_per_kg'], 0, ',', '.') : '-' }}</p>
            </div>
        @else
            <div class="glass rounded-2xl p-4 col-span-2 md:col-span-4 text-center lw-muted text-sm">
                Anda tidak punya akses untuk melihat angka finansial musim ini.
            </div>
        @endif
    </div>

    @if ($canViewCost && $profitLoss['unsold_inventory_value'] > 0)
        <div class="glass rounded-2xl p-4 mb-6 flex justify-between items-center">
            <span class="text-sm lw-muted">Nilai stok panen yang belum terjual</span>
            <span class="font-semibold">Rp {{ number_format($profitLoss['unsold_inventory_value'], 0, ',', '.') }}</span>
        </div>
    @endif

    {{-- Rincian pengeluaran --}}
    <div class="glass rounded-2xl overflow-hidden">
        <div class="px-4 py-3 font-semibold text-sm" style="border-bottom:1px solid rgba(148,163,184,.15)">Rincian Pengeluaran Musim Ini</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left lw-muted text-xs uppercase tracking-wide" style="border-bottom:1px solid rgba(148,163,184,.15)">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        @if ($canViewCost)
                            <th class="px-4 py-3">Jumlah</th>
                        @endif
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr style="border-bottom:1px solid rgba(148,163,184,.08)">
                            <td class="px-4 py-3 lw-muted">{{ $expense->date?->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $expense->category?->name }}</td>
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
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center lw-muted">Belum ada pengeluaran tercatat untuk musim ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canViewCost)
        <p class="text-xs lw-muted mt-3">
            Catatan: "Biaya Produksi" di atas cuma menghitung yang sudah "✓ Disetujui" — beban yang masih "Menunggu" terlihat di tabel tapi belum masuk hitungan, sampai disetujui di halaman Beban.
        </p>
    @endif
</div>
