<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Greenhouse extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'code', 'name', 'location', 'length', 'width',
        'area', 'status', 'notes',
    ];

    // construction_date / construction_cost intentionally NOT stored here —
    // per architecture note #3, construction cost is derived from Assets
    // (category = greenhouse_construction), so there's only one source of
    // truth for that figure. The Asset model now exists as of Phase 5, so
    // the class_exists() guards used in Phase 1–4 (when Asset didn't
    // exist yet) are no longer needed and have been removed here.

    /**
     * Roadmap tambahan #5 — "luas harusnya otomatis terhitung dari
     * perkalian panjang x lebar tanpa isi manual". A `saving` hook
     * (fires for EVERY save path — API, Livewire, factory, seeder —
     * per Phase 10's single-source-of-truth goal) recomputes `area`
     * whenever both length and width are present, overriding whatever
     * value (if any) was passed in for `area`. If either dimension is
     * missing, `area` is left untouched — some existing greenhouses
     * may only have a manually-entered area from before this field
     * existed, and this must not blank those out.
     */
    protected static function booted(): void
    {
        static::saving(function (Greenhouse $greenhouse) {
            if ($greenhouse->length !== null && $greenhouse->width !== null) {
                $greenhouse->area = round((float) $greenhouse->length * (float) $greenhouse->width, 2);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function constructionCost(): float
    {
        return (float) $this->assets()
            ->where('category', 'greenhouse_construction')
            ->sum('value');
    }
}
