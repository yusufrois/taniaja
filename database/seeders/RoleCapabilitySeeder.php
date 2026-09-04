<?php

namespace Database\Seeders;

use App\Services\Auth\RoleMatrixService;
use Illuminate\Database\Seeder;

/**
 * Bug fix (see RoleMatrixService's docblock for the full root-cause
 * story): this now delegates entirely to RoleMatrixService, which
 * creates any of the 5 standard roles a company is STILL MISSING
 * (not just attaching permissions to roles that already happen to
 * exist, like before) — running this seeder is now the fix for a
 * company that hit the "cuma ada Company Owner" bug, this app's own
 * demo company included. Safe to re-run any number of times.
 */
class RoleCapabilitySeeder extends Seeder
{
    public function run(): void
    {
        app(RoleMatrixService::class)->provisionAllCompanies();
    }
}
