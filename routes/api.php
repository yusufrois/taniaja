<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ActivityTemplateController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetCategoryController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EmployeeLoanController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PieceWorkLogController;
use App\Http\Controllers\Api\V1\WorkTypeController;
use App\Http\Controllers\Api\V1\InputItemController;
use App\Http\Controllers\Api\V1\InputPurchaseController;
use App\Http\Controllers\Api\V1\InputUsageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CapitalTransactionController;
use App\Http\Controllers\Api\V1\CropController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DebtController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\GreenhouseController;
use App\Http\Controllers\Api\V1\HarvestController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\PlantLossController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\StockBatchController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\DeliveryNoteController;
use App\Http\Controllers\Api\V1\ChartOfAccountController;
use App\Http\Controllers\Api\V1\JournalEntryController;
use App\Http\Controllers\Api\V1\AccountingReportController;
use App\Http\Controllers\Api\V1\AccountTransferController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VarietyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/register-company', [AuthController::class, 'registerCompany']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // RBAC Fase A3 — User Management: lets an Owner (or anyone
        // granted 'user.*' permissions) add/manage staff within their
        // OWN company. Previously the only way a User ever got created
        // was company registration or a seeder — this fills that gap.
        Route::apiResource('users', UserController::class);
        Route::patch('users/{user}/suspend', [UserController::class, 'suspend']);
        Route::patch('users/{user}/reactivate', [UserController::class, 'reactivate']);
        Route::get('users/{user}/warnings', [UserController::class, 'warnings']);
        Route::post('users/{user}/warnings', [UserController::class, 'warn']);

        // Phase 2 — Master Data
        Route::apiResource('greenhouses', GreenhouseController::class);
        Route::apiResource('crops', CropController::class);
        Route::apiResource('varieties', VarietyController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('grades', GradeController::class);
        Route::apiResource('expense-categories', ExpenseCategoryController::class)
            ->parameters(['expense-categories' => 'expense_category']);

        // Phase 3 — Musim Tanam
        Route::apiResource('seasons', SeasonController::class);
        Route::get('seasons/{season}/dashboard', [SeasonController::class, 'dashboard']);

        // Phase 4 — Budidaya
        Route::apiResource('activity-templates', ActivityTemplateController::class)
            ->parameters(['activity-templates' => 'activity_template']);
        Route::post('activity-templates/{activity_template}/items', [ActivityTemplateController::class, 'storeItem']);
        Route::delete('activity-templates/{activity_template}/items/{item}', [ActivityTemplateController::class, 'destroyItem']);
        Route::get('seasons/{season}/schedules', [ScheduleController::class, 'index']);
        Route::post('seasons/{season}/generate-schedule', [ScheduleController::class, 'generate']);
        Route::post('schedules/{schedule}/complete', [ScheduleController::class, 'complete']);
        Route::post('schedules/{schedule}/skip', [ScheduleController::class, 'skip']);
        Route::get('seasons/{season}/activities', [ActivityController::class, 'index']);
        Route::post('activities', [ActivityController::class, 'store']);
        Route::get('activities/{activity}', [ActivityController::class, 'show']);
        Route::delete('activities/{activity}', [ActivityController::class, 'destroy']);

        // Phase 5 — Keuangan
        Route::apiResource('capital-transactions', CapitalTransactionController::class)
            ->parameters(['capital-transactions' => 'capital_transaction'])
            ->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('assets', AssetController::class);
        Route::apiResource('asset-categories', AssetCategoryController::class)
            ->parameters(['asset-categories' => 'asset_category'])
            ->only(['index', 'store', 'destroy']);
        Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve']);
        Route::apiResource('debts', DebtController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('debts/{debt}/payments', [DebtController::class, 'storePayment']);

        // Phase 6 — Panen & Pembelian (Stock)
        Route::apiResource('harvests', HarvestController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'storePayment']);
        Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'pdf']); // roadmap Fase E — Nota Panen
        Route::get('stock-batches', [StockBatchController::class, 'index']);
        Route::post('stock-batches/{batch}/sell', [StockBatchController::class, 'sell']);

        // Phase 7 — Penjualan
        Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('sales/{sale}/payments', [SaleController::class, 'storePayment']);

        // Phase 8 — Laporan
        Route::get('reports/seasons/{season}/hpp', [ReportController::class, 'seasonHpp']);
        Route::get('reports/seasons/{season}/profit-loss', [ReportController::class, 'seasonProfitLoss']);
        Route::get('reports/greenhouses/{greenhouse}/performance', [ReportController::class, 'greenhousePerformance']);
        Route::get('reports/company/profit-loss', [ReportController::class, 'companyProfitLoss']);
        Route::get('reports/activities/today', [ReportController::class, 'todayActivities']);
        // Roadmap Tambahan Fase J & K
        Route::get('reports/seasons/{season}/traceability', [ReportController::class, 'seasonTraceability']);
        Route::get('reports/suppliers/{supplier}/history', [ReportController::class, 'supplierHistory']);
        // Roadmap Tambahan Fase D — Surat Jalan
        Route::apiResource('delivery-notes', DeliveryNoteController::class)
            ->parameters(['delivery-notes' => 'delivery_note'])
            ->only(['index', 'store', 'show', 'destroy']);
        Route::get('delivery-notes/{delivery_note}/pdf', [DeliveryNoteController::class, 'pdf']);

        // Roadmap Tambahan Fase L1 — Akuntansi: Bagan Akun + Jurnal
        Route::get('chart-of-accounts', [ChartOfAccountController::class, 'index']);
        Route::post('chart-of-accounts', [ChartOfAccountController::class, 'store']);
        Route::get('chart-of-accounts/{chart_of_account}', [ChartOfAccountController::class, 'show']);
        Route::post('chart-of-accounts/seed-defaults', [ChartOfAccountController::class, 'seedDefaults']);
        Route::apiResource('journal-entries', JournalEntryController::class)
            ->parameters(['journal-entries' => 'journal_entry'])
            ->only(['index', 'store', 'show', 'destroy']);

        // Roadmap Tambahan Fase L3 — Buku Besar & Neraca Saldo
        Route::get('accounting/ledger/{chart_of_account}', [AccountingReportController::class, 'ledger']);
        Route::get('accounting/trial-balance', [AccountingReportController::class, 'trialBalance']);
        // Roadmap Tambahan Fase L4 — Neraca & Laba Rugi formal
        Route::get('accounting/balance-sheet', [AccountingReportController::class, 'balanceSheet']);
        Route::get('accounting/income-statement', [AccountingReportController::class, 'incomeStatement']);
        // Roadmap Tambahan Fase L5 — Kas & Bank: transfer antar akun
        Route::apiResource('account-transfers', AccountTransferController::class)
            ->parameters(['account-transfers' => 'account_transfer'])
            ->only(['index', 'store', 'show']);

        // Fase F — fitur yang sudah ada di katalog permission sejak
        // lama tapi baru sekarang benar-benar dibangun controllernya
        Route::get('company', [CompanyController::class, 'show']);
        Route::patch('company', [CompanyController::class, 'update']);
        Route::get('audit-logs', [AuditLogController::class, 'index']);

        // Phase 8b — Tanaman Mati (Plant Loss)
        Route::get('seasons/{season}/plant-losses', [PlantLossController::class, 'index']);
        Route::post('plant-losses', [PlantLossController::class, 'store']);
        Route::delete('plant-losses/{plant_loss}', [PlantLossController::class, 'destroy']);

        // RBAC Roadmap Fase C — Stok Input Pertanian (pupuk, dll)
        Route::apiResource('input-items', InputItemController::class)
            ->parameters(['input-items' => 'input_item']);
        Route::apiResource('input-purchases', InputPurchaseController::class)
            ->parameters(['input-purchases' => 'input_purchase'])
            ->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('input-usages', InputUsageController::class)
            ->parameters(['input-usages' => 'input_usage'])
            ->only(['index', 'store', 'show', 'destroy']);

        // Roadmap Tambahan Fase G — Absensi
        Route::apiResource('employees', EmployeeController::class);
        Route::post('attendances/check-in', [AttendanceController::class, 'checkIn']);
        Route::apiResource('attendances', AttendanceController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        // Roadmap Tambahan Fase H — Penggajian
        Route::apiResource('work-types', WorkTypeController::class)
            ->parameters(['work-types' => 'work_type']);
        Route::apiResource('piece-work-logs', PieceWorkLogController::class)
            ->parameters(['piece-work-logs' => 'piece_work_log'])
            ->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('employee-loans', EmployeeLoanController::class)
            ->parameters(['employee-loans' => 'employee_loan'])
            ->only(['index', 'store', 'show']);
        Route::get('payroll-periods', [PayrollController::class, 'index']);
        Route::get('payroll-periods/{payroll_period}', [PayrollController::class, 'show']);
        Route::post('payroll-periods/generate', [PayrollController::class, 'generate']);
        Route::post('payroll-periods/{payroll_period}/finalize', [PayrollController::class, 'finalize']);
        Route::get('payslips/{payslip}', [PayrollController::class, 'showPayslip']);

        // Roadmap Tambahan Fase I — Tugas dari Atasan
        Route::apiResource('tasks', TaskController::class);
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);
        Route::post('tasks/{task}/steps', [TaskController::class, 'addStep']);
        Route::patch('tasks/{task}/steps/{step}/toggle', [TaskController::class, 'toggleStep']);
        Route::post('tasks/{task}/follow-up', [TaskController::class, 'followUp']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
    });
});
