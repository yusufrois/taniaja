<?php

namespace App\Observers;

use App\Models\AccountTransfer;
use App\Services\Accounting\AccountingPostingService;

class AccountTransferObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(AccountTransfer $transfer): void
    {
        $this->accounting->postAccountTransfer($transfer);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(AccountTransfer $transfer): void
    {
        $this->accounting->voidFor('account_transfer', $transfer->id);
    }
}
