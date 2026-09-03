<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HarvestItem extends Model
{
    use HasFactory, BelongsToCompany;
    // No SoftDeletes — a harvest item that's wrong should be corrected
    // via the parent Harvest record or a new correcting entry; it's not
    // an independent financial transaction the way Expense/Debt are.

    protected $fillable = [
        'company_id', 'harvest_id', 'grade_id', 'quantity', 'weight',
        'quality_notes', 'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function harvest()
    {
        return $this->belongsTo(Harvest::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function stockBatch()
    {
        return $this->hasOne(StockBatch::class);
    }
}
