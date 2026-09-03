<?php

namespace App\Observers;

use App\Models\SalePayment;
use App\Services\Accounting\AccountingPostingService;

class SalePaymentObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(SalePayment $payment): void
    {
        $this->accounting->postSalePayment($payment);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(SalePayment $payment): void
    {
        $this->accounting->voidFor('sale_payment', $payment->id);
    }
}
