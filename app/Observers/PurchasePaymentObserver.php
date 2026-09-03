<?php

namespace App\Observers;

use App\Models\PurchasePayment;
use App\Services\Accounting\AccountingPostingService;

class PurchasePaymentObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(PurchasePayment $payment): void
    {
        $this->accounting->postPurchasePayment($payment);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(PurchasePayment $payment): void
    {
        $this->accounting->voidFor('purchase_payment', $payment->id);
    }
}
