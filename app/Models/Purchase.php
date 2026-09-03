<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'supplier_id', 'crop_id', 'variety_id', 'grade_id',
        'purchase_date', 'quantity', 'unit_price', 'total_amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    // Trading operational costs (transport, sorting, ...) linked to this
    // purchase via expenses.purchase_id — see migration 000005.
    public function landedCostExpenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function stockBatch()
    {
        return $this->hasOne(StockBatch::class);
    }

    /**
     * Added for roadmap Fase K (per-supplier purchase history report)
     * — "siapa staff yang beli" per the person's own requirement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function totalPaid(): Attribute
    {
        return Attribute::get(fn () => (float) $this->payments()->sum('amount'));
    }

    protected function remaining(): Attribute
    {
        return Attribute::get(fn () => (float) $this->total_amount - $this->total_paid);
    }

    // Same derived-status pattern as Debt (Phase 5) — never stored,
    // always correct relative to actual payments recorded.
    protected function paymentStatus(): Attribute
    {
        return Attribute::get(function () {
            if ($this->remaining <= 0) {
                return 'paid';
            }

            return $this->total_paid > 0 ? 'partial' : 'unpaid';
        });
    }
}
