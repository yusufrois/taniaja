<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWarning extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'issued_by', 'reason',
        'acknowledged_at', 'confirmed_at', 'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'confirmed_at' => 'datetime',
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

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
