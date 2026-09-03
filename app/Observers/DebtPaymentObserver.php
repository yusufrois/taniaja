<?php

namespace App\Observers;

use App\Models\DebtPayment;
use App\Services\Accounting\AccountingPostingService;

class DebtPaymentObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(DebtPayment $payment): void
    {
        $this->accounting->postDebtPayment($payment);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(DebtPayment $payment): void
    {
        $this->accounting->voidFor('debt_payment', $payment->id);
    }
}
