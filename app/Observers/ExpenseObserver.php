<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\Accounting\AccountingPostingService;

/**
 * Fires for EVERY Expense creation path — including InputUsage's
 * auto-created Expenses (Fase C revision) — since they all go through
 * the same Expense::create() Eloquent call regardless of which
 * controller/service initiated it. This is exactly why Fase L2 uses
 * model Observers rather than manually calling the posting service
 * from inside each individual controller: a future new Expense-
 * creation path automatically gets journaled too, nothing to remember
 * to wire up.
 */
class ExpenseObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(Expense $expense): void
    {
        $this->accounting->postExpense($expense);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(Expense $expense): void
    {
        $this->accounting->voidFor('expense', $expense->id);
    }
}
