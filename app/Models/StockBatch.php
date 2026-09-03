<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory, BelongsToCompany;
    // No SoftDeletes — a StockBatch is a derived/generated ledger entry,
    // not a primary financial transaction; it disappears if its source
    // (Harvest item or Purchase) is deleted (cascadeOnDelete in migration).

    protected $fillable = [
        'company_id', 'source_type', 'harvest_item_id', 'purchase_id',
        'crop_id', 'variety_id', 'grade_id', 'acquired_date',
        'quantity_acquired', 'quantity_available', 'unit_cost', 'status',
    ];

    protected function casts(): array
    {
        return [
            'acquired_date' => 'date',
            'quantity_acquired' => 'decimal:2',
            'quantity_available' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function harvestItem()
    {
        return $this->belongsTo(HarvestItem::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
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

    public function sales()
    {
        return $this->hasMany(StockBatchSale::class);
    }
}
