<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoanDeduction extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'employee_loan_id', 'payslip_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function employeeLoan()
    {
        return $this->belongsTo(EmployeeLoan::class);
    }

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
