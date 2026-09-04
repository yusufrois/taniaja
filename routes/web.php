<?php

use App\Http\Controllers\LandingController;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterCompany;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard;
use App\Livewire\Greenhouse\Manage as GreenhouseManage;
use App\Livewire\Crop\Manage as CropManage;
use App\Livewire\Variety\Manage as VarietyManage;
use App\Livewire\Supplier\Manage as SupplierManage;
use App\Livewire\Customer\Manage as CustomerManage;
use App\Livewire\Grade\Manage as GradeManage;
use App\Livewire\ExpenseCategory\Manage as ExpenseCategoryManage;
use App\Livewire\Season\Manage as SeasonManage;
use App\Livewire\Harvest\Manage as HarvestManage;
use App\Livewire\Stock\Manage as StockManage;
use App\Livewire\Season\Detail as SeasonDetail;
use App\Livewire\Expense\Manage as ExpenseManage;
use App\Livewire\ChartOfAccount\Manage as ChartOfAccountManage;
use App\Livewire\Capital\Manage as CapitalManage;
use App\Livewire\Debt\Manage as DebtManage;
use App\Livewire\Asset\Manage as AssetManage;
use App\Livewire\AssetCategory\Manage as AssetCategoryManage;
use App\Livewire\Report\Manage as ReportManage;
use App\Livewire\Settings\ModuleToggle;
use App\Livewire\Staff\Manage as StaffManage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Session-based WEB routes (Livewire UI) — completely separate auth
 * mechanism from routes/api.php's Sanctum token auth. Both point at the
 * same Users table (registration itself is shared via
 * CompanyRegistrationService), but a web session here does NOT issue
 * or require a Sanctum token, and vice versa: a Postman/Flutter client
 * using a Sanctum token is unaffected by anything in this file.
 *
 * This is the DEFINITIVE, fully-reconciled version — every 'web.*'
 * route ever added across the whole Phase 9/10 UI build is confirmed
 * present here (a prior chain of packages had accidentally branched
 * from an older copy partway through, silently dropping 'web.reports'
 * — this file was rebuilt route-by-route against every layout link to
 * make sure that class of gap can't happen again).
 */

// =============================================================================
// [1] LANDING PAGE
// Scope: Halaman publik — tidak memerlukan autentikasi.
// Layout: resources/views/layouts/landing.blade.php
// =============================================================================

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/tentang', [LandingController::class, 'tentang'])->name('landing.tentang');
Route::get('/blog', [LandingController::class, 'blog'])->name('landing.blog');
Route::get('/kontak', [LandingController::class, 'kontak'])->name('landing.kontak');

// =============================================================================
// [2] AUTH — Guest only (login, register, forgot/reset password)
// Scope: Halaman autentikasi — hanya bisa diakses saat BELUM login.
// =============================================================================

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', RegisterCompany::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// =============================================================================
// [3] STORE
// Scope: Halaman toko/e-commerce publik — dikerjakan di sesi terpisah.
// Prefix rencana: /store/...
// =============================================================================

// TODO: Route store akan ditambahkan di sini.

// =============================================================================
// [4] APP / ADMIN
// Scope: Aplikasi internal — wajib login (middleware auth).
// Semua nama route diprefix 'web.' agar tidak bentrok dengan route API
// di routes/api.php yang menggunakan Route::apiResource (tanpa prefix nama).
// =============================================================================

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // --- Master Data ---
    Route::get('/greenhouses', GreenhouseManage::class)->name('web.greenhouses');
    Route::get('/crops', CropManage::class)->name('web.crops');
    Route::get('/varieties', VarietyManage::class)->name('web.varieties');
    Route::get('/suppliers', SupplierManage::class)->name('web.suppliers');
    Route::get('/customers', CustomerManage::class)->name('web.customers');
    Route::get('/grades', GradeManage::class)->name('web.grades');
    Route::get('/expense-categories', ExpenseCategoryManage::class)->name('web.expense-categories');
    Route::get('/asset-categories', AssetCategoryManage::class)->name('web.asset-categories');

    // --- Musim Tanam ---
    Route::get('/seasons', SeasonManage::class)->name('web.seasons');
    Route::get('/harvests', HarvestManage::class)->name('web.harvests');
    Route::get('/stock', StockManage::class)->name('web.stock');
    Route::get('/seasons/{season}', SeasonDetail::class)->name('web.seasons.detail');

    // --- Keuangan ---
    Route::get('/expenses', ExpenseManage::class)->name('web.expenses');
    Route::get('/chart-of-accounts', ChartOfAccountManage::class)->name('web.chart-of-accounts');
    Route::get('/capital', CapitalManage::class)->name('web.capital');
    Route::get('/debts', DebtManage::class)->name('web.debts');
    Route::get('/assets', AssetManage::class)->name('web.assets');

    // --- Laporan & Pengaturan ---
    Route::get('/reports', ReportManage::class)->name('web.reports');
    Route::get('/settings/modules', ModuleToggle::class)->name('web.settings.modules');
    Route::get('/staff', StaffManage::class)->name('web.staff');

    // --- Logout ---
    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
