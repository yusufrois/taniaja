<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Season extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'greenhouse_id', 'crop_id', 'variety_id',
        'season_name', 'planting_date', 'estimated_harvest_date',
        'actual_harvest_date', 'plant_count', 'target_yield',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'estimated_harvest_date' => 'date',
            'actual_harvest_date' => 'date',
            'target_yield' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function greenhouse()
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Added for roadmap Fase J (traceability report) — was missing
     * despite Harvest.season_id existing since Phase 6.
     */
    public function harvests()
    {
        return $this->hasMany(Harvest::class);
    }

    public function plantLosses()
    {
        return $this->hasMany(PlantLoss::class);
    }

    /**
     * HST (Hari Setelah Tanam) — Section 8 of the architecture doc.
     * Computed live from today's date, never stored, so it is always
     * accurate without a scheduled job to keep a stored column in sync.
     * Once the season is closed (completed/cancelled), HST freezes at
     * the harvest date (or planting date for cancelled) instead of
     * continuing to count days after the season is no longer running.
     */
    protected function hst(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->planting_date) {
                return null;
            }

            $referenceDate = match ($this->status) {
                'completed' => $this->actual_harvest_date ?? Carbon::today(),
                'cancelled' => $this->planting_date,
                default => Carbon::today(),
            };

            return (int) $this->planting_date->diffInDays($referenceDate, false);
        });
    }

    /**
     * "Alive right now" plant count — plant_count (Section 8, the
     * original planting record) minus all recorded PlantLoss quantities.
     * plant_count itself is intentionally NEVER mutated by a loss entry:
     * it stays the historical "how many were planted" figure, while this
     * accessor gives the current living count. Never goes below 0 even
     * if losses were over-recorded (defensive floor, not a validation
     * substitute — StorePlantLossRequest is what actually prevents
     * recording more deaths than are alive).
     */
    protected function currentPlantCount(): Attribute
    {
        return Attribute::get(function () {
            if ($this->plant_count === null) {
                return null;
            }

            $totalLost = (int) $this->plantLosses()->sum('quantity');

            return max(0, $this->plant_count - $totalLost);
        });
    }

    protected function survivalRatePercent(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->plant_count) {
                return null;
            }

            return round(($this->current_plant_count / $this->plant_count) * 100, 2);
        });
    }
}
