<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'date', 'status', 'check_in_time',
        'check_in_lat', 'check_in_lng', 'notes', 'recorded_by',
    ];

    /**
     * 'date:Y-m-d' — NOT plain 'date' — is important here, not just
     * cosmetic. A plain 'date' cast serializes to the connection's
     * full DATETIME format when saving ("2026-09-01 00:00:00"), while
     * AttendanceController::checkIn()'s updateOrCreate() searches using
     * a plain 'Y-m-d' string. That mismatch made the upsert lookup
     * always miss (looking for "2026-09-01", finding "2026-09-01
     * 00:00:00" in the row), so it kept trying to INSERT a duplicate
     * instead of updating — tripping the unique constraint on repeat
     * check-ins. Explicit 'Y-m-d' keeps storage and search consistent.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'check_in_lat' => 'decimal:7',
            'check_in_lng' => 'decimal:7',
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

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
