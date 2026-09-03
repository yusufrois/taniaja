<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Attach this trait to every model that has a `company_id` column.
 *
 * It automatically:
 *  1. Scopes every query (SELECT/UPDATE/DELETE) to the authenticated
 *     user's company, so a developer can NEVER accidentally leak data
 *     across tenants by forgetting a ->where('company_id', ...).
 *  2. Auto-fills company_id on create() from the authenticated user.
 *  3. Super Admin (company_id === null on the user) bypasses the scope
 *     entirely and can see all companies — used only in admin tooling.
 *
 * Any model using this trait MUST have a `company_id` foreign key.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Apply the scope only when there is an authenticated user.
        // Console commands / queue jobs must set the tenant explicitly
        // via ->withoutGlobalScope or a job-level company context.
        static::addGlobalScope('company', function (Builder $builder) {
            $user = auth()->user();

            if (! $user) {
                return; // e.g. running in console/seeders — caller is responsible
            }

            // Super Admin has company_id = null → sees everything.
            if ($user->company_id === null && $user->isSuperAdmin()) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.company_id', $user->company_id);
        });

        static::creating(function (Model $model) {
            if (empty($model->company_id) && auth()->check()) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    /**
     * Explicit escape hatch for jobs/console/admin tooling that must
     * operate across tenants. Never use this in a request-scoped
     * controller without an authorization check first.
     */
    public function scopeAllCompanies(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}
