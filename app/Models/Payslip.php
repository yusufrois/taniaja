<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'payroll_period_id', 'employee_id', 'pay_type',
        'gross_amount', 'deduction_amount', 'net_amount', 'detail',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'detail' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loanDeductions()
    {
        return $this->hasMany(EmployeeLoanDeduction::class);
    }
}
