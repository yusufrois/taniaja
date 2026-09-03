<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'address', 'phone', 'email', 'logo',
        'tax_number', 'currency', 'timezone', 'status', 'enabled_modules',
    ];

    protected function casts(): array
    {
        return [
            'enabled_modules' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function greenhouses()
    {
        return $this->hasMany(Greenhouse::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Roadmap tambahan — "toggle per modul" (petani vs tengkulak vs
     * campuran). A module missing from the map, or the whole map
     * being null (never configured), defaults to ENABLED — so this
     * never silently hides a module for a company that hasn't visited
     * the settings page yet, and adding a brand-new togglable module
     * later doesn't require a data migration for existing companies.
     */
    public function hasModuleEnabled(string $module): bool
    {
        return (bool) ($this->enabled_modules[$module] ?? true);
    }
}
