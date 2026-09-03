<?php

namespace App\Observers;

use App\Models\StockBatchSale;
use App\Services\Accounting\AccountingPostingService;

class StockBatchSaleObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(StockBatchSale $sale): void
    {
        $this->accounting->postStockBatchSale($sale);
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus transaksi
     * ini harus membatalkan (void) jurnalnya juga, bukan cuma
     * menghapus baris transaksinya sendiri — supaya Laporan/Neraca
     * Saldo tetap benar setelah dihapus.
     */
    public function deleted(StockBatchSale $sale): void
    {
        $this->accounting->voidFor('stock_batch_sale', $sale->id);
    }
}
