<?php

namespace App\Observers;

use App\Models\CapitalTransaction;
use App\Services\Accounting\AccountingPostingService;

class CapitalTransactionObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(CapitalTransaction $transaction): void
    {
        $this->accounting->postCapitalTransaction($transaction);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(CapitalTransaction $transaction): void
    {
        $this->accounting->voidFor('capital_transaction', $transaction->id);
    }
}
