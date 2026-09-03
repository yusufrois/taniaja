<?php

namespace App\Http\Requests;

use App\Models\PlantLoss;
use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlantLossRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PlantLoss::class);
    }

    public function rules(): array
    {
        return [
            'season_id' => [
                'required',
                Rule::exists('seasons', 'id')->where('company_id', $this->user()->company_id),
            ],
            'date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'cause' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Can't record more plant deaths than are actually still alive —
     * same "can't exceed what's available" principle as debt/purchase/
     * sale payments (Phase 5-7) and stock batch sales (Phase 6), applied
     * here to a living count instead of money or inventory weight.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $seasonId = $this->input('season_id');
            $quantity = (int) $this->input('quantity');

            if (! $seasonId || $quantity <= 0) {
                return; // already caught by the basic rules above
            }

            $season = Season::find($seasonId);

            if (! $season || $season->plant_count === null) {
                return; // no plant_count recorded for this season — nothing to validate against
            }

            if ($quantity > $season->current_plant_count) {
                $validator->errors()->add(
                    'quantity',
                    "Jumlah tanaman mati ({$quantity}) melebihi jumlah tanaman yang masih hidup ".
                    "saat ini ({$season->current_plant_count})."
                );
            }
        });
    }
}
