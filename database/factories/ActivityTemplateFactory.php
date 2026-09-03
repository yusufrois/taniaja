<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityTemplateFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);

        return [
            'company_id' => $company->id,
            'variety_id' => $variety->id,
            'name' => 'Template '.fake()->word(),
        ];
    }
}
