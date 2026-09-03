<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'creditor_name' => fake()->company(),
            'debt_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'amount' => fake()->randomFloat(2, 1000000, 20000000),
        ];
    }
}
