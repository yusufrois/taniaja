<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory, BelongsToCompany;
    // No SoftDeletes — a line item is corrected by voiding/adjusting the
    // parent Sale, not deleted independently (same reasoning as HarvestItem).

    protected $fillable = [
        'company_id', 'sale_id', 'stock_batch_id', 'description',
        'quantity', 'unit', 'price', 'subtotal', 'cost', 'profit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'cost' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}
