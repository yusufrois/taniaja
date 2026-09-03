<?php

namespace App\Services\Sales;

use App\Models\Sale;
use Illuminate\Support\Carbon;

/**
 * Format: INV/{company_code}/{YYYYMM}/{sequence}, e.g. INV/LWI/202608/0001.
 * Sequence resets monthly per company — computed from the count of sales
 * already in that company+month, not a separate counter table, since
 * this app's per-company invoice volume doesn't need the extra
 * complexity of a dedicated sequence generator (Aturan #43).
 */
class InvoiceNumberGenerator
{
    public function generate(int $companyId, string $companyCode, ?Carbon $date = null): string
    {
        $date ??= now();
        $yearMonth = $date->format('Ym');

        $countThisMonth = Sale::where('company_id', $companyId)
            ->where('invoice_number', 'like', "INV/{$companyCode}/{$yearMonth}/%")
            ->count();

        $sequence = str_pad((string) ($countThisMonth + 1), 4, '0', STR_PAD_LEFT);

        return "INV/{$companyCode}/{$yearMonth}/{$sequence}";
    }
}
