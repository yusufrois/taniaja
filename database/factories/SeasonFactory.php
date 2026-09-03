<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Greenhouse;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);

        return [
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim '.fake()->numberBetween(1, 20),
            'planting_date' => now()->subDays(fake()->numberBetween(0, 60)),
            'plant_count' => fake()->numberBetween(500, 2000),
            'status' => 'active',
        ];
    }
}
