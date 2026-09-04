<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TaniAja' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_fav.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="min-h-screen">
    {{--
        Layout is now: full-width topbar FIRST (top of the whole page),
        then a row below it containing the sidebar (left) + main content
        — not sidebar-and-topbar side by side. x-data stays on this
        outermost wrapper so both the topbar and the sidebar/drawer below
        can share the same open/close state. Alpine needs no separate
        install — Livewire 3 bundles and boots it automatically.
    --}}
    <div x-data="{ sidebarOpen: true, mobileMenuOpen: false, dataMasterOpen: true, keuanganOpen: true }" class="flex flex-col min-h-screen">

        {{-- Topbar: spans the FULL page width, above everything else --}}
        <header class="glass relative m-4 mb-0 rounded-2xl px-4 py-3 flex items-center justify-between gap-3" style="z-index:10">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="mobileMenuOpen = true" class="lg:hidden btn-secondary px-2.5 py-1.5" title="Menu">☰</button>

                <a href="{{ route('dashboard') }}" class="shrink-0" title="Kembali ke Dashboard">
                    <img src="{{ asset('images/logo.png') }}" alt="TaniAja" class="h-10 md:h-12 w-auto object-contain">
                </a>

                <span class="text-xs lw-muted hidden md:inline whitespace-nowrap border-l pl-3"
                      style="border-color:rgba(148,163,184,.18)"
                      x-data="{ now: '' }"
                      x-init="
                          const tick = () => now = new Date().toLocaleString('id-ID', { weekday:'short', day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });
                          tick(); setInterval(tick, 1000 * 30);
                      "
                      x-text="now"></span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <livewire:notification-bell />
                <button class="btn-secondary px-2.5 py-1.5 text-sm" title="Pengaturan (segera hadir)">⚙️</button>

                <div class="flex items-center gap-2 pl-2 pr-1 py-1 rounded-full" style="background:rgba(15,32,24,.6)">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                         style="background:linear-gradient(135deg,#4ade80,#22c55e);color:#031008">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="text-sm hidden md:inline whitespace-nowrap">{{ auth()->user()->name }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary text-xs px-3 py-1.5" title="Keluar">🚪</button>
                </form>
            </div>
        </header>

        {{-- Below the topbar: sidebar (left) + main content, side by side --}}
        <div class="flex flex-1">
            <aside
                class="glass relative m-4 mr-0 rounded-2xl hidden lg:flex flex-col shrink-0 overflow-visible transition-all duration-200"
                style="z-index:10"
                :class="sidebarOpen ? 'w-56' : 'w-16'"
            >
                <nav class="flex-1 px-2 pt-4 space-y-1.5 overflow-y-auto overflow-x-hidden">
                    <a href="{{ route('dashboard') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span>📊</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Dashboard</span>
                    </a>

                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    @can('viewAny', App\Models\Greenhouse::class)
                    <a href="{{ route('web.greenhouses') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.greenhouses') ? 'active' : '' }}">
                        <span>🏡</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Greenhouse</span>
                    </a>
                    @endcan
                    @endif

                    @php
                        $dataMasterItems = collect([
                            ['web.crops', '🌾', 'Komoditas', \App\Models\Crop::class],
                            ['web.varieties', '🌱', 'Varietas', \App\Models\Variety::class],
                            ['web.suppliers', '🚜', 'Supplier', \App\Models\Supplier::class],
                            ['web.customers', '🏪', 'Customer', \App\Models\Customer::class],
                            ['web.grades', '🏷️', 'Grade', \App\Models\Grade::class],
                            ['web.expense-categories', '📁', 'Kategori Beban', \App\Models\ExpenseCategory::class],
                            ['web.asset-categories', '🗂️', 'Kategori Aset', \App\Models\AssetCategory::class],
                        ])->filter(fn ($item) => auth()->user()->can('viewAny', $item[3]));
                    @endphp
                    @if ($dataMasterItems->isNotEmpty())
                    <button @click="dataMasterOpen = !dataMasterOpen" x-show="sidebarOpen" x-transition.opacity
                            class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Data Master</span>
                        <span x-text="dataMasterOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="dataMasterOpen || !sidebarOpen" x-transition class="space-y-1.5">
                    @foreach ($dataMasterItems as [$routeName, $icon, $label, $modelClass])
                        <a href="{{ route($routeName) }}"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs($routeName) ? 'active' : '' }}">
                            <span>{{ $icon }}</span>
                            <span x-show="sidebarOpen" x-transition.opacity>{{ $label }}</span>
                        </a>
                    @endforeach
                    </div>
                    @endif

                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    @can('viewAny', App\Models\Season::class)
                    <a href="{{ route('web.seasons') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.seasons') ? 'active' : '' }}">
                        <span>🌾</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Musim Tanam</span>
                    </a>
                    @endcan
                    @endif

                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    @can('viewAny', App\Models\Harvest::class)
                    <a href="{{ route('web.harvests') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.harvests') ? 'active' : '' }}">
                        <span>🧺</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Panen</span>
                    </a>
                    @endcan
                    @endif

                    @can('viewAny', App\Models\StockBatchSale::class)
                    <a href="{{ route('web.stock') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.stock') ? 'active' : '' }}">
                        <span>📦</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Stok / Gudang</span>
                    </a>
                    @endcan

                    @php
                        $keuanganItems = collect([
                            ['web.chart-of-accounts', '📒', 'Bagan Akun', \App\Models\ChartOfAccount::class],
                            ['web.expenses', '💸', 'Beban', \App\Models\Expense::class],
                            ['web.capital', '🏦', 'Modal', \App\Models\CapitalTransaction::class],
                            ['web.debts', '💳', 'Hutang', \App\Models\Debt::class],
                            ['web.assets', '🏗️', 'Aset Tetap', \App\Models\Asset::class],
                        ])->filter(fn ($item) => auth()->user()->can('viewAny', $item[3]));
                    @endphp
                    @if ($keuanganItems->isNotEmpty())
                    <button @click="keuanganOpen = !keuanganOpen" x-show="sidebarOpen" x-transition.opacity
                            class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Keuangan</span>
                        <span x-text="keuanganOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="keuanganOpen || !sidebarOpen" x-transition class="space-y-1.5">
                    @foreach ($keuanganItems as [$routeName, $icon, $label, $modelClass])
                        <a href="{{ route($routeName) }}"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs($routeName) ? 'active' : '' }}">
                            <span>{{ $icon }}</span>
                            <span x-show="sidebarOpen" x-transition.opacity>{{ $label }}</span>
                        </a>
                    @endforeach
                    </div>
                    @endif

                    @if (auth()->user()->hasPermission('report.view'))
                    <a href="{{ route('web.reports') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.reports') ? 'active' : '' }}">
                        <span>📈</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Laporan</span>
                    </a>
                    @endif

                    @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->subordinates()->exists())
                        <a href="{{ route('web.staff') }}"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.staff') ? 'active' : '' }}">
                            <span>👥</span>
                            <span x-show="sidebarOpen" x-transition.opacity>Kelola Staf</span>
                        </a>
                    @endif

                    @can('update', auth()->user()->company)
                        <a href="{{ route('web.settings.modules') }}"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.settings.modules') ? 'active' : '' }}">
                            <span>🧩</span>
                            <span x-show="sidebarOpen" x-transition.opacity>Pengaturan Modul</span>
                        </a>
                    @endcan
                </nav>

                <button @click="sidebarOpen = !sidebarOpen"
                        class="absolute top-1/2 -right-2.5 -translate-y-1/2 w-3.5 h-10 rounded-full flex items-center justify-center text-[10px] z-10 lw-muted"
                        style="background:rgba(255,255,255,.06); border:1px solid rgba(148,163,184,.18); backdrop-filter:blur(4px);"
                        title="Buka/tutup sidebar">
                    <span x-show="sidebarOpen">‹</span>
                    <span x-show="!sidebarOpen">›</span>
                </button>
            </aside>

            {{-- Mobile drawer: dark overlay + sliding panel, only exists below lg --}}
            <div x-show="mobileMenuOpen" x-transition.opacity
                 @click="mobileMenuOpen = false"
                 class="fixed inset-0 bg-black/60 z-40 lg:hidden" style="display:none"></div>

            <aside x-show="mobileMenuOpen"
                   x-transition:enter="transition ease-out duration-200"
                   x-transition:enter-start="opacity-0 -translate-x-full"
                   x-transition:enter-end="opacity-100 translate-x-0"
                   x-transition:leave="transition ease-in duration-150"
                   x-transition:leave-start="opacity-100 translate-x-0"
                   x-transition:leave-end="opacity-0 -translate-x-full"
                   class="glass fixed left-0 top-0 bottom-0 w-64 z-50 p-4 flex flex-col lg:hidden"
                   style="display:none" @click.outside="mobileMenuOpen = false">
                <div class="flex items-center justify-between mb-4">
                    <a href="{{ route('dashboard') }}" @click="mobileMenuOpen = false">
                        <img src="{{ asset('images/logo.png') }}" alt="TaniAja" class="h-8">
                    </a>
                    <button @click="mobileMenuOpen = false" class="btn-secondary px-2 py-1 text-sm">✕</button>
                </div>
                <nav class="space-y-1.5">
                    <a href="{{ route('dashboard') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span>📊</span><span>Dashboard</span>
                    </a>
                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    @can('viewAny', App\Models\Greenhouse::class)
                    <a href="{{ route('web.greenhouses') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.greenhouses') ? 'active' : '' }}">
                        <span>🏡</span><span>Greenhouse</span>
                    </a>
                    @endcan
                    @endif
                    @if ($dataMasterItems->isNotEmpty())
                    <button @click="dataMasterOpen = !dataMasterOpen" class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Data Master</span>
                        <span x-text="dataMasterOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="dataMasterOpen" x-transition class="space-y-1.5">
                    @foreach ($dataMasterItems as [$routeName, $icon, $label, $modelClass])
                        <a href="{{ route($routeName) }}" @click="mobileMenuOpen = false"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs($routeName) ? 'active' : '' }}">
                            <span>{{ $icon }}</span><span>{{ $label }}</span>
                        </a>
                    @endforeach
                    </div>
                    @endif
                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    @can('viewAny', App\Models\Season::class)
                    <a href="{{ route('web.seasons') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.seasons') ? 'active' : '' }}">
                        <span>🌾</span><span>Musim Tanam</span>
                    </a>
                    @endcan
                    @endif
                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    @can('viewAny', App\Models\Harvest::class)
                    <a href="{{ route('web.harvests') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.harvests') ? 'active' : '' }}">
                        <span>🧺</span><span>Panen</span>
                    </a>
                    @endcan
                    @endif
                    @can('viewAny', App\Models\StockBatchSale::class)
                    <a href="{{ route('web.stock') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.stock') ? 'active' : '' }}">
                        <span>📦</span><span>Stok / Gudang</span>
                    </a>
                    @endcan
                    @if ($keuanganItems->isNotEmpty())
                    <button @click="keuanganOpen = !keuanganOpen" class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Keuangan</span>
                        <span x-text="keuanganOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="keuanganOpen" x-transition class="space-y-1.5">
                    @foreach ($keuanganItems as [$routeName, $icon, $label, $modelClass])
                        <a href="{{ route($routeName) }}" @click="mobileMenuOpen = false"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs($routeName) ? 'active' : '' }}">
                            <span>{{ $icon }}</span><span>{{ $label }}</span>
                        </a>
                    @endforeach
                    </div>
                    @endif
                    @if (auth()->user()->hasPermission('report.view'))
                    <a href="{{ route('web.reports') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.reports') ? 'active' : '' }}">
                        <span>📈</span><span>Laporan</span>
                    </a>
                    @endif
                    @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->subordinates()->exists())
                        <a href="{{ route('web.staff') }}" @click="mobileMenuOpen = false"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.staff') ? 'active' : '' }}">
                            <span>👥</span><span>Kelola Staf</span>
                        </a>
                    @endif
                    @can('update', auth()->user()->company)
                        <a href="{{ route('web.settings.modules') }}" @click="mobileMenuOpen = false"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.settings.modules') ? 'active' : '' }}">
                            <span>🧩</span><span>Pengaturan Modul</span>
                        </a>
                    @endcan
                </nav>
            </aside>

            <main class="flex-1 min-w-0 p-4">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
