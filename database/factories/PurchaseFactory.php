<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();
        $quantity = fake()->randomFloat(2, 100, 500);
        $unitPrice = fake()->randomFloat(2, 5000, 15000);

        return [
            'company_id' => $company->id,
            'supplier_id' => Supplier::factory()->create(['company_id' => $company->id])->id,
            'crop_id' => Crop::factory()->create(['company_id' => $company->id])->id,
            'purchase_date' => now(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => round($quantity * $unitPrice, 2),
        ];
    }
}
