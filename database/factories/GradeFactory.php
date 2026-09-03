<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->randomElement(['Grade A', 'Grade B', 'Grade C', 'Reject']),
            'sort_order' => fake()->numberBetween(0, 3),
        ];
    }
}
