<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'greenhouse_id', 'season_id', 'creditor_name',
        'debt_date', 'due_date', 'amount', 'installment_months', 'chart_of_account_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'debt_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    protected function totalPaid(): Attribute
    {
        return Attribute::get(fn () => (float) $this->payments()->sum('amount'));
    }

    protected function remaining(): Attribute
    {
        return Attribute::get(fn () => (float) $this->amount - $this->total_paid);
    }

    /**
     * Status is fully derived — Section 15's "total hutang / dibayar /
     * tersisa" dashboard needs this to always be correct even if a
     * payment is added/deleted directly, without a stored status column
     * that could drift out of sync (same reasoning as HST and Schedule's
     * overdue in earlier phases).
     */
    protected function status(): Attribute
    {
        return Attribute::get(function () {
            if ($this->remaining <= 0) {
                return 'paid';
            }

            if ($this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday()) {
                return 'overdue';
            }

            if ($this->total_paid > 0) {
                return 'partial';
            }

            return 'unpaid';
        });
    }

    /**
     * Roadmap tambahan — "jadwal 24 bulan otomatis, penanda cicilan
     * ke berapa". Fixed equal installments (amount / installment_months),
     * one due date per month starting one month after debt_date.
     * Null when this debt has no installment plan (lump-sum loan).
     */
    protected function installmentAmount(): Attribute
    {
        return Attribute::get(fn () => $this->installment_months
            ? round((float) $this->amount / $this->installment_months, 2)
            : null
        );
    }

    /**
     * How many installments are FULLY covered by cumulative payments
     * so far — e.g. 3 of 24. Deliberately NOT tied to individual
     * payments being linked to a specific installment number (that
     * would need a much bigger schema change); instead it allocates
     * whatever's been paid, in total, against the schedule in order —
     * same "computed live" principle as status()/remaining() above.
     */
    protected function installmentsPaid(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->installment_months || ! $this->installment_amount) {
                return null;
            }

            return min($this->installment_months, (int) floor($this->total_paid / $this->installment_amount));
        });
    }

    /**
     * Full month-by-month schedule for the "Riwayat & Jadwal" detail
     * view — each entry's is_paid is derived the same way as
     * installments_paid above (cumulative allocation, not a specific
     * payment link).
     */
    public function schedule(): \Illuminate\Support\Collection
    {
        if (! $this->installment_months || ! $this->debt_date) {
            return collect();
        }

        $installmentAmount = $this->installment_amount;
        $totalPaid = $this->total_paid;

        return collect(range(1, $this->installment_months))->map(function (int $i) use ($installmentAmount, $totalPaid) {
            return [
                'installment_no' => $i,
                'due_date' => $this->debt_date->copy()->addMonths($i),
                'amount' => $installmentAmount,
                'is_paid' => $totalPaid >= round($installmentAmount * $i, 2),
            ];
        });
    }
}
