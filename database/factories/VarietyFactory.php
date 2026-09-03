<?php

namespace Database\Factories;

use App\Models\Crop;
use Illuminate\Database\Eloquent\Factories\Factory;

class VarietyFactory extends Factory
{
    public function definition(): array
    {
        $crop = Crop::factory()->create();

        return [
            'company_id' => $crop->company_id,
            'crop_id' => $crop->id,
            'name' => fake()->unique()->word(),
        ];
    }
}
