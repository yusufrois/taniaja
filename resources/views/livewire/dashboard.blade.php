<div>
    <h1 class="text-xl font-semibold mb-6">
        Selamat datang, <span class="brand">{{ auth()->user()->name }}</span> 👋
    </h1>

    @if ($myWarnings->isNotEmpty())
        <div class="glass rounded-2xl p-4 mb-6" style="border-left:3px solid #fbbf24">
            <p class="font-semibold text-sm mb-2" style="color:#fbbf24">⚠️ Peringatan untuk Anda</p>
            <div class="space-y-2">
                @foreach ($myWarnings as $warning)
                    <div class="text-xs flex items-start justify-between gap-3">
                        <div>
                            <p>{{ $warning->reason }}</p>
                            <p class="lw-muted mt-0.5">{{ $warning->created_at?->translatedFormat('d M Y H:i') }}</p>
                            @if ($warning->acknowledged_at)
                                <p class="mt-1" style="color:#60a5fa">Menunggu konfirmasi atasan</p>
                            @endif
                        </div>
                        @unless ($warning->acknowledged_at)
                            <button wire:click="acknowledgeWarning({{ $warning->id }})" class="btn-secondary px-3 py-1 text-xs shrink-0">Sudah Baca</button>
                        @endunless
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($canViewReports)
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <div class="glass p-4" style="border-left:3px solid #38bdf8;">
                <p class="lw-label !mb-2">💰 Total Revenue</p>
                <p class="text-xl font-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            </div>
            <div class="glass p-4" style="border-left:3px solid #fb7185;">
                <p class="lw-label !mb-2">📤 Total Expense</p>
                <p class="text-xl font-bold">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
            </div>
            <div class="glass p-4" style="border-left:3px solid {{ $netProfit >= 0 ? '#4ade80' : '#fb7185' }};">
                <p class="lw-label !mb-2">📈 Net Profit</p>
                <p class="text-xl font-bold" style="color:{{ $netProfit >= 0 ? '#4ade80' : '#fb7185' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </p>
            </div>
            <div class="glass p-4" style="border-left:3px solid #fbbf24;">
                <p class="lw-label !mb-2">🧾 Total Hutang</p>
                <p class="text-xl font-bold">Rp {{ number_format($totalDebtRemaining, 0, ',', '.') }}</p>
            </div>
            <div class="glass p-4" style="border-left:3px solid #2dd4bf;">
                <p class="lw-label !mb-2">🏡 Greenhouse Aktif</p>
                <p class="text-xl font-bold">{{ $activeGreenhouses }}</p>
            </div>
            <div class="glass p-4" style="border-left:3px solid #a78bfa;">
                <p class="lw-label !mb-2">🌾 Musim Aktif</p>
                <p class="text-xl font-bold">{{ $activeSeasons }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass p-4">
                <h2 class="text-sm font-semibold mb-3 lw-muted">Revenue / Expense / Profit (6 Bulan Terakhir)</h2>
                <canvas id="monthlyTrendChart" height="220"></canvas>
            </div>
            <div class="glass p-4">
                <h2 class="text-sm font-semibold mb-3 lw-muted">Expense per Greenhouse (Top 5)</h2>
                <canvas id="expenseByGreenhouseChart" height="220"></canvas>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const monthlyTrend = @json($monthlyTrend);
                const expenseByGreenhouse = @json($expenseByGreenhouse);

                Chart.defaults.color = '#91a69a';
                Chart.defaults.borderColor = 'rgba(74,222,128,.12)';

                new Chart(document.getElementById('monthlyTrendChart'), {
                    type: 'line',
                    data: {
                        labels: monthlyTrend.map(m => m.label),
                        datasets: [
                            { label: 'Revenue', data: monthlyTrend.map(m => m.revenue), borderColor: '#38bdf8', backgroundColor: '#38bdf820', fill: true, tension: 0.3 },
                            { label: 'Expense', data: monthlyTrend.map(m => m.expense), borderColor: '#fb7185', backgroundColor: '#fb718520', fill: true, tension: 0.3 },
                            { label: 'Gross Profit', data: monthlyTrend.map(m => m.gross_profit), borderColor: '#4ade80', backgroundColor: '#4ade8020', fill: true, tension: 0.3 },
                        ],
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
                });

                new Chart(document.getElementById('expenseByGreenhouseChart'), {
                    type: 'bar',
                    data: {
                        labels: expenseByGreenhouse.map(g => g.label),
                        datasets: [{
                            label: 'Total Expense',
                            data: expenseByGreenhouse.map(g => g.total),
                            backgroundColor: ['#4ade80', '#38bdf8', '#fbbf24', '#fb7185', '#a78bfa'],
                            borderRadius: 6,
                        }],
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } },
                });
            });
        </script>
    @elseif ($canViewActivities)
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="glass p-4" style="border-left:3px solid #2dd4bf;">
                <p class="lw-label !mb-2">📋 Aktivitas Hari Ini</p>
                <p class="text-2xl font-bold">{{ $todayActivitiesCount }}</p>
            </div>
            <div class="glass p-4" style="border-left:3px solid #fb7185;">
                <p class="lw-label !mb-2">⏰ Aktivitas Terlambat</p>
                <p class="text-2xl font-bold" style="color:#fb7185">{{ $overdueActivitiesCount }}</p>
            </div>
        </div>
    @else
        <p class="lw-muted">Tidak ada ringkasan yang bisa ditampilkan untuk peran Anda saat ini.</p>
    @endif
</div>
