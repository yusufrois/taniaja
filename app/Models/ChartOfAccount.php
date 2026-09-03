<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['company_id', 'code', 'name', 'type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Running balance for this account — computed live from all its
     * posted (non-voided) journal lines, same "computed live, never
     * stored" principle used throughout this app. Asset/Expense accounts
     * increase with Debit; Liability/Equity/Revenue increase with
     * Credit — standard double-entry sign convention.
     */
    public function balance(): float
    {
        $totalDebit = (float) $this->lines()->sum('debit');
        $totalCredit = (float) $this->lines()->sum('credit');

        return in_array($this->type, ['asset', 'expense'])
            ? round($totalDebit - $totalCredit, 2)
            : round($totalCredit - $totalDebit, 2);
    }
}
