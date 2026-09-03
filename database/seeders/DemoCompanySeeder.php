<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Greenhouse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'LWI'],
            [
                'name' => 'LadangWohIjo',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
            ]
        );

        $roleSlugs = [
            'owner' => 'Company Owner',
            'manager' => 'Manager',
            'supervisor' => 'Supervisor',
            'worker' => 'Worker',
            'finance' => 'Finance',
        ];

        $roles = [];
        foreach ($roleSlugs as $slug => $name) {
            $roles[$slug] = Role::firstOrCreate(
                ['company_id' => $company->id, 'slug' => $slug],
                ['name' => $name, 'is_system' => true]
            );
        }

        foreach ($roleSlugs as $slug => $name) {
            $user = User::firstOrCreate(
                ['email' => "{$slug}@ladangwohijo.test"],
                [
                    'company_id' => $company->id,
                    'name' => $name.' Demo',
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]
            );
            $user->roles()->syncWithoutDetaching([$roles[$slug]->id]);
        }

        foreach (['GH-A', 'GH-B'] as $code) {
            Greenhouse::firstOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                ['name' => "Greenhouse {$code}", 'status' => 'active']
            );
        }
    }
}
