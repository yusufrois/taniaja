<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class GreenhouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'GH-'.strtoupper(fake()->unique()->lexify('?')),
            'name' => 'Greenhouse '.fake()->word(),
            'status' => 'active',
        ];
    }
}
