<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * App's OWN in-app notification record — distinct from Laravel's
 * built-in Illuminate\Notifications system (which this app does not
 * use). Kept intentionally simple: type/title/body/data, read_at.
 */
class Notification extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'user_id', 'type', 'title', 'body', 'data', 'read_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
