<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Pekerjaan '.fake()->unique()->word(),
            'unit' => 'kg',
            'rate' => 5000,
        ];
    }
}
