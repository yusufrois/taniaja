<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputUsage extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'input_item_id', 'greenhouse_id', 'season_id',
        'activity_id', 'used_date', 'quantity', 'cost', 'expense_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'used_date' => 'date',
            'quantity' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function inputItem()
    {
        return $this->belongsTo(InputItem::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
