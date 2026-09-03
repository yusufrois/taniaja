<?php

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only checks EACH LINE's basic shape (valid account, numeric
 * debit/credit) — the actual double-entry BALANCE rule (sum debit ===
 * sum credit, exactly one of debit/credit per line) is enforced in
 * JournalEntryService, not here, since that's a cross-line business
 * rule rather than a per-field validation rule.
 */
class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', JournalEntry::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => [
                'required', Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId),
            ],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
