<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Harvest extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'season_id', 'greenhouse_id', 'variety_id',
        'harvest_date', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['harvest_date' => 'date'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function items()
    {
        return $this->hasMany(HarvestItem::class);
    }

    public function totalWeight(): float
    {
        return (float) $this->items()->sum('weight');
    }
}
