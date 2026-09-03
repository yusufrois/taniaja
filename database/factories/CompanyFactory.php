<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->lexify('???')).fake()->unique()->numberBetween(100, 999),
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
        ];
    }
}
