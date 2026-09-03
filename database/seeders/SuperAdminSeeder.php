<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fase A5: the 'super-admin' Role has existed since Phase 1
 * (RolePermissionSeeder), but no seeder ever created an actual USER
 * attached to it — there was literally no way to log in as Super
 * Admin at all until now. Demo credentials below are for local
 * development only; change the password before any real deployment.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::whereNull('company_id')->where('slug', 'super-admin')->first();

        if (! $role) {
            return; // RolePermissionSeeder hasn't run — nothing to attach to yet
        }

        $user = User::firstOrCreate(
            ['email' => 'superadmin@taniaja.test'],
            [
                'company_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
