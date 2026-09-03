<?php

namespace App\Observers;

use App\Models\Asset;
use App\Services\Accounting\AccountingPostingService;

/**
 * Roadmap tambahan #6 — Asset now posts to accounting the same way
 * Modal/Hutang/Beban already do. deleted() also voids the journal on
 * delete, same fix as bug report #7 applied to every other type.
 */
class AssetObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function created(Asset $asset): void
    {
        $this->accounting->postAsset($asset);
    }

    public function deleted(Asset $asset): void
    {
        $this->accounting->voidFor('asset', $asset->id);
    }
}
