<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'phone', 'email', 'address', 'type'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
