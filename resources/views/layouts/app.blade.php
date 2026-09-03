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
                    <a href="{{ route('web.greenhouses') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.greenhouses') ? 'active' : '' }}">
                        <span>🏡</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Greenhouse</span>
                    </a>
                    @endif

                    <button @click="dataMasterOpen = !dataMasterOpen" x-show="sidebarOpen" x-transition.opacity
                            class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Data Master</span>
                        <span x-text="dataMasterOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="dataMasterOpen || !sidebarOpen" x-transition class="space-y-1.5">
                    @foreach ([
                        ['web.crops', '🌾', 'Komoditas'],
                        ['web.varieties', '🌱', 'Varietas'],
                        ['web.suppliers', '🚜', 'Supplier'],
                        ['web.customers', '🏪', 'Customer'],
                        ['web.grades', '🏷️', 'Grade'],
                        ['web.expense-categories', '📁', 'Kategori Beban'],
                        ['web.asset-categories', '🗂️', 'Kategori Aset'],
                    ] as [$routeName, $icon, $label])
                        <a href="{{ route($routeName) }}"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs($routeName) ? 'active' : '' }}">
                            <span>{{ $icon }}</span>
                            <span x-show="sidebarOpen" x-transition.opacity>{{ $label }}</span>
                        </a>
                    @endforeach
                    </div>

                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    <a href="{{ route('web.seasons') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.seasons') ? 'active' : '' }}">
                        <span>🌾</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Musim Tanam</span>
                    </a>
                    @endif

                    <button @click="keuanganOpen = !keuanganOpen" x-show="sidebarOpen" x-transition.opacity
                            class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Keuangan</span>
                        <span x-text="keuanganOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="keuanganOpen || !sidebarOpen" x-transition class="space-y-1.5">
                    <a href="{{ route('web.chart-of-accounts') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.chart-of-accounts') ? 'active' : '' }}">
                        <span>📒</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Bagan Akun</span>
                    </a>
                    <a href="{{ route('web.expenses') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.expenses') ? 'active' : '' }}">
                        <span>💸</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Beban</span>
                    </a>
                    <a href="{{ route('web.capital') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.capital') ? 'active' : '' }}">
                        <span>🏦</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Modal</span>
                    </a>
                    <a href="{{ route('web.debts') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.debts') ? 'active' : '' }}">
                        <span>💳</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Hutang</span>
                    </a>
                    <a href="{{ route('web.assets') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.assets') ? 'active' : '' }}">
                        <span>🏗️</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Aset Tetap</span>
                    </a>
                    </div>

                    <a href="{{ route('web.reports') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.reports') ? 'active' : '' }}">
                        <span>📈</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Laporan</span>
                    </a>

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
                    <a href="{{ route('web.greenhouses') }}"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.greenhouses') ? 'active' : '' }}">
                        <span>🏡</span><span>Greenhouse</span>
                    </a>
                    @endif
                    <button @click="dataMasterOpen = !dataMasterOpen" class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Data Master</span>
                        <span x-text="dataMasterOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="dataMasterOpen" x-transition class="space-y-1.5">
                    @foreach ([
                        ['web.crops', '🌾', 'Komoditas'],
                        ['web.varieties', '🌱', 'Varietas'],
                        ['web.suppliers', '🚜', 'Supplier'],
                        ['web.customers', '🏪', 'Customer'],
                        ['web.grades', '🏷️', 'Grade'],
                        ['web.expense-categories', '📁', 'Kategori Beban'],
                        ['web.asset-categories', '🗂️', 'Kategori Aset'],
                    ] as [$routeName, $icon, $label])
                        <a href="{{ route($routeName) }}" @click="mobileMenuOpen = false"
                           class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs($routeName) ? 'active' : '' }}">
                            <span>{{ $icon }}</span><span>{{ $label }}</span>
                        </a>
                    @endforeach
                    </div>
                    @if (auth()->user()->company->hasModuleEnabled('budidaya'))
                    <a href="{{ route('web.seasons') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.seasons') ? 'active' : '' }}">
                        <span>🌾</span><span>Musim Tanam</span>
                    </a>
                    @endif
                    <button @click="keuanganOpen = !keuanganOpen" class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider lw-muted font-semibold">
                        <span>Keuangan</span>
                        <span x-text="keuanganOpen ? '▾' : '▸'"></span>
                    </button>
                    <div x-show="keuanganOpen" x-transition class="space-y-1.5">
                    <a href="{{ route('web.chart-of-accounts') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.chart-of-accounts') ? 'active' : '' }}">
                        <span>📒</span><span>Bagan Akun</span>
                    </a>
                    <a href="{{ route('web.expenses') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.expenses') ? 'active' : '' }}">
                        <span>💸</span><span>Beban</span>
                    </a>
                    <a href="{{ route('web.capital') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.capital') ? 'active' : '' }}">
                        <span>🏦</span><span>Modal</span>
                    </a>
                    <a href="{{ route('web.debts') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.debts') ? 'active' : '' }}">
                        <span>💳</span><span>Hutang</span>
                    </a>
                    <a href="{{ route('web.assets') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.assets') ? 'active' : '' }}">
                        <span>🏗️</span><span>Aset Tetap</span>
                    </a>
                    </div>
                    <a href="{{ route('web.reports') }}" @click="mobileMenuOpen = false"
                       class="tab-btn w-full !inline-flex justify-start {{ request()->routeIs('web.reports') ? 'active' : '' }}">
                        <span>📈</span><span>Laporan</span>
                    </a>
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
