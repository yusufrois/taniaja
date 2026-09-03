<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Services\Accounting\AccountingPostingService;

class PurchaseObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(Purchase $purchase): void
    {
        $this->accounting->postPurchase($purchase);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(Purchase $purchase): void
    {
        $this->accounting->voidFor('purchase', $purchase->id);
    }
}
