<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'name', 'phone', 'position', 'status',
        'pay_type', 'monthly_salary', 'daily_rate',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function pieceWorkLogs()
    {
        return $this->hasMany(PieceWorkLog::class);
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Sum of remainingBalance() across all this employee's loans — what
     * PayrollCalculationService deducts from, and what a Payslip's
     * detail references.
     */
    public function totalOutstandingLoans(): float
    {
        return round((float) $this->loans()->get()->sum(fn ($loan) => $loan->remainingBalance()), 2);
    }
}
