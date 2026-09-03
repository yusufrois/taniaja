<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'title', 'description', 'assigned_to', 'assigned_by',
        'status', 'related_task_id', 'due_date',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date:Y-m-d'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function relatedTask()
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }

    /**
     * Follow-up tasks pointing back at this one — "tugas susulan"
     * confirmed to be separate Task rows marked related, not steps
     * appended to this Task.
     */
    public function followUps()
    {
        return $this->hasMany(Task::class, 'related_task_id');
    }

    public function steps()
    {
        return $this->hasMany(TaskStep::class)->orderBy('order');
    }
}
