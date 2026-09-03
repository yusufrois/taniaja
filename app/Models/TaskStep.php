<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class TaskStep extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'task_id', 'description', 'is_done', 'order', 'completed_at'];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
