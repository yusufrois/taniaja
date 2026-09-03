<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variety extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = ['company_id', 'crop_id', 'name', 'notes'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }
}
