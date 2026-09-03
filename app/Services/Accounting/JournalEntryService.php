<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The core "double-entry" guarantee lives here: every JournalEntry's
 * lines must balance (SUM debit === SUM credit), and each line must be
 * EITHER a debit OR a credit, never both/neither. From Fase L2 onward,
 * OTHER services (SalesService, PurchaseController, etc.) will call
 * this SAME create() method to auto-post — never construct
 * JournalEntry/JournalEntryLine directly elsewhere, so this validation
 * can never be bypassed.
 */
class JournalEntryService
{
    /**
     * @param  array  $lines  each: ['chart_of_account_id' => int, 'debit' => float, 'credit' => float, 'notes' => ?string]
     */
    public function create(array $header, array $lines, User $user): JournalEntry
    {
        $this->assertLinesAreValid($lines);

        return DB::transaction(function () use ($header, $lines, $user) {
            $entry = JournalEntry::create([
                'company_id' => $user->company_id,
                'date' => $header['date'],
                'description' => $header['description'],
                'reference_type' => $header['reference_type'] ?? null,
                'reference_id' => $header['reference_id'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'company_id' => $user->company_id,
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    private function assertLinesAreValid(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Jurnal butuh minimal 2 baris (1 Debit, 1 Kredit).',
            ]);
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $i => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$i}" => 'Satu baris jurnal tidak boleh punya Debit dan Kredit sekaligus.',
                ]);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$i}" => 'Setiap baris jurnal harus punya nilai Debit ATAU Kredit lebih dari 0.',
                ]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw ValidationException::withMessages([
                'lines' => "Jurnal tidak seimbang: total Debit (Rp ".number_format($totalDebit, 0, ',', '.').
                    ") harus sama dengan total Kredit (Rp ".number_format($totalCredit, 0, ',', '.').").",
            ]);
        }
    }
}
