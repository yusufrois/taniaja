<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBatchSale extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'stock_batch_id', 'customer_id', 'sale_date',
        'quantity_sold', 'sale_price_per_unit', 'revenue', 'cost', 'profit',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'quantity_sold' => 'decimal:2',
            'sale_price_per_unit' => 'decimal:2',
            'revenue' => 'decimal:2',
            'cost' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
