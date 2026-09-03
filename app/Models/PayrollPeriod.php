<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'year', 'month', 'status', 'finalized_at', 'finalized_by'];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
