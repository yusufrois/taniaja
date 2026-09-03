<?php

namespace App\Observers;

use App\Models\Debt;
use App\Services\Accounting\AccountingPostingService;

class DebtObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(Debt $debt): void
    {
        $this->accounting->postDebt($debt);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(Debt $debt): void
    {
        $this->accounting->voidFor('debt', $debt->id);
    }
}
