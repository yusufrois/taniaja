<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false; // only created_at, set via useCurrent() in migration

    protected $fillable = [
        'company_id', 'user_id', 'action', 'model', 'model_id',
        'old_value', 'new_value', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Was never defined until Fase F — AuditLog had no relations at
     * all before AuditLogController needed to eager-load who performed
     * each action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
