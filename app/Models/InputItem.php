<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'unit', 'category', 'default_expense_category_id', 'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function defaultExpenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class, 'default_expense_category_id');
    }

    public function purchases()
    {
        return $this->hasMany(InputPurchase::class);
    }

    public function usages()
    {
        return $this->hasMany(InputUsage::class);
    }

    /**
     * Current stock on hand — computed live (total bought minus total
     * used), never stored as a column, same reasoning as HST (Phase 3),
     * Season::currentPlantCount() (Plant Loss feature), and Debt's
     * remaining (Phase 5): a derived number that could otherwise drift
     * out of sync with its two source ledgers.
     */
    public function currentStock(): float
    {
        $bought = (float) $this->purchases()->sum('quantity');
        $used = (float) $this->usages()->sum('quantity');

        return max(0, round($bought - $used, 2));
    }

    /**
     * Weighted-average cost per unit across ALL purchases to date —
     * this pools stock together rather than tracking discrete batches
     * (unlike StockBatch, which DOES track per-batch cost for resale
     * goods). Fertilizer/input stock doesn't need FIFO/batch-specific
     * costing the way harvested produce does, so a single running
     * average is a reasonable, simpler basis.
     *
     * IMPORTANT: this is a LIVE figure that shifts as new purchases
     * come in. InputUsageController::store() calls this ONCE at
     * creation time and freezes the result onto InputUsage.cost — it
     * is NEVER read again retroactively for an existing usage, so a
     * later purchase at a different price never silently changes what
     * an earlier usage "cost" (same principle as StockBatch's
     * "unit_cost frozen at first sale", Phase 6).
     */
    public function averageUnitCost(): float
    {
        $totalQuantity = (float) $this->purchases()->sum('quantity');

        if ($totalQuantity <= 0) {
            return 0.0;
        }

        $totalSpent = (float) $this->purchases()->sum('total_amount');

        return round($totalSpent / $totalQuantity, 2);
    }
}
