<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PieceWorkLog extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'work_type_id', 'date',
        'quantity', 'rate', 'amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d', // see Attendance model for why not plain 'date'
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }
}
