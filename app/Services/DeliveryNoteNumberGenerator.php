<?php

namespace App\Services;

use App\Models\DeliveryNote;
use Illuminate\Support\Carbon;

/**
 * Format: SJ/{company_code}/{YYYYMM}/{sequence} — same pattern as
 * InvoiceNumberGenerator (Phase 7), just "SJ" (Surat Jalan) instead
 * of "INV". Sequence resets monthly per company.
 */
class DeliveryNoteNumberGenerator
{
    public function generate(int $companyId, string $companyCode, ?Carbon $date = null): string
    {
        $date ??= now();
        $yearMonth = $date->format('Ym');

        $countThisMonth = DeliveryNote::where('company_id', $companyId)
            ->where('delivery_number', 'like', "SJ/{$companyCode}/{$yearMonth}/%")
            ->count();

        $sequence = str_pad((string) ($countThisMonth + 1), 4, '0', STR_PAD_LEFT);

        return "SJ/{$companyCode}/{$yearMonth}/{$sequence}";
    }
}
