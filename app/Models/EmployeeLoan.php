<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLoan extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = ['company_id', 'employee_id', 'date', 'amount', 'notes', 'created_by'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
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

    public function deductions()
    {
        return $this->hasMany(EmployeeLoanDeduction::class);
    }

    /**
     * Never stored — same "computed live" principle as Debt.remaining
     * (Phase 5) and InputItem.currentStock() (Fase C).
     */
    public function remainingBalance(): float
    {
        $deducted = (float) $this->deductions()->sum('amount');

        return max(0, round((float) $this->amount - $deducted, 2));
    }
}
