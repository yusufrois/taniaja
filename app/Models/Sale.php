<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'invoice_number', 'date', 'due_date', 'customer_id',
        'greenhouse_id', 'season_id', 'subtotal', 'discount', 'tax', 'total',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    protected function totalPaid(): Attribute
    {
        return Attribute::get(fn () => (float) $this->payments()->sum('amount'));
    }

    protected function remaining(): Attribute
    {
        return Attribute::get(fn () => (float) $this->total - $this->total_paid);
    }

    // Same derived-status pattern as Debt (Phase 5) and Purchase (Phase 6)
    // — Section 21: Unpaid/Partial/Paid/Overdue, always correct relative
    // to actual payments, never a stored column that can drift.
    protected function paymentStatus(): Attribute
    {
        return Attribute::get(function () {
            if ($this->remaining <= 0) {
                return 'paid';
            }

            if ($this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday()) {
                return 'overdue';
            }

            return $this->total_paid > 0 ? 'partial' : 'unpaid';
        });
    }

    protected function totalProfit(): Attribute
    {
        return Attribute::get(fn () => (float) $this->items()->sum('profit'));
    }
}
