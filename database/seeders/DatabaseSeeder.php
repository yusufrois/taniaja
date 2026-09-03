<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class, // needs the super-admin Role from RolePermissionSeeder above
            DemoCompanySeeder::class,
            RoleCapabilitySeeder::class, // must run after roles + permissions both exist
        ]);
    }
}
