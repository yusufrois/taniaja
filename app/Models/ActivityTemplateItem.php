<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityTemplateItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'activity_template_id', 'hst', 'activity_name', 'category',
        'description', 'material', 'dosage', 'unit', 'instruction',
        'estimated_duration_minutes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function template()
    {
        return $this->belongsTo(ActivityTemplate::class, 'activity_template_id');
    }
}
