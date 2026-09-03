<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Schedule extends Model
{
    use HasFactory, BelongsToCompany;
    // No SoftDeletes: schedules are disposable planning artifacts, not
    // financial or audit-sensitive records — Section 30 only requires
    // soft delete for important financial transactions.

    protected $fillable = [
        'company_id', 'season_id', 'activity_template_item_id', 'scheduled_date',
        'activity_name', 'category', 'instruction', 'status',
    ];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function templateItem()
    {
        return $this->belongsTo(ActivityTemplateItem::class, 'activity_template_item_id');
    }

    public function activity()
    {
        return $this->hasOne(Activity::class);
    }

    /**
     * "Overdue" is a live, computed status (Section 10), not a stored
     * one — a schedule is overdue the moment today passes scheduled_date
     * while it's still pending, without needing a cron job to flip it.
     * The stored `status` column only ever holds pending/in_progress/
     * completed/skipped; 'overdue' is what the API reports when pending
     * has passed its date.
     */
    protected function effectiveStatus(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status === 'pending' && $this->scheduled_date->isPast() && ! $this->scheduled_date->isToday()) {
                return 'overdue';
            }

            return $this->status;
        });
    }
}
