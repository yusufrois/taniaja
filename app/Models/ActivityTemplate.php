<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityTemplate extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = ['company_id', 'variety_id', 'name'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function items()
    {
        return $this->hasMany(ActivityTemplateItem::class)->orderBy('hst');
    }
}
