<?php

namespace App\Http\Requests;

use App\Models\Season;
use App\Models\Variety;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('season'));
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'greenhouse_id' => [
                'sometimes', 'required',
                Rule::exists('greenhouses', 'id')->where('company_id', $companyId),
            ],
            'crop_id' => [
                'sometimes', 'required',
                Rule::exists('crops', 'id')->where('company_id', $companyId),
            ],
            'variety_id' => [
                'sometimes', 'required',
                Rule::exists('varieties', 'id')->where('company_id', $companyId),
            ],
            'season_name' => ['sometimes', 'required', 'string', 'max:255'],
            'planting_date' => ['sometimes', 'required', 'date'],
            'estimated_harvest_date' => ['nullable', 'date'],
            'actual_harvest_date' => ['nullable', 'date'],
            'plant_count' => ['nullable', 'integer', 'min:0'],
            'target_yield' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['planning', 'active', 'harvesting', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Season $season */
            $season = $this->route('season');

            // Roadmap tambahan #8 — harvest dates can't be before
            // planting_date. Uses the EXISTING season's planting_date
            // as fallback when this partial update doesn't touch that
            // field (declarative after_or_equal:planting_date would
            // incorrectly compare against nothing in that case).
            $plantingDate = $this->input('planting_date', $season->planting_date?->toDateString());
            foreach (['estimated_harvest_date', 'actual_harvest_date'] as $field) {
                $value = $this->input($field);
                if ($value && $plantingDate && \Carbon\Carbon::parse($value)->lt(\Carbon\Carbon::parse($plantingDate))) {
                    $label = $field === 'estimated_harvest_date' ? 'Estimasi panen' : 'Tanggal panen aktual';
                    $validator->errors()->add($field, "{$label} tidak boleh sebelum tanggal tanam.");
                }
            }

            $cropId = $this->input('crop_id', $season->crop_id);
            $varietyId = $this->input('variety_id', $season->variety_id);

            if ($cropId && $varietyId) {
                $ok = Variety::where('id', $varietyId)->where('crop_id', $cropId)->exists();
                if (! $ok) {
                    $validator->errors()->add(
                        'variety_id',
                        'Varietas yang dipilih tidak termasuk dalam crop yang dipilih.'
                    );
                }
            }

            $newStatus = $this->input('status');
            $greenhouseId = $this->input('greenhouse_id', $season->greenhouse_id);

            if ($newStatus && in_array($newStatus, ['planning', 'active', 'harvesting'], true)) {
                $hasOtherRunningSeason = Season::where('greenhouse_id', $greenhouseId)
                    ->whereIn('status', ['planning', 'active', 'harvesting'])
                    ->where('id', '!=', $season->id)
                    ->exists();

                if ($hasOtherRunningSeason) {
                    $validator->errors()->add(
                        'greenhouse_id',
                        'Greenhouse ini sudah memiliki musim tanam lain yang masih berjalan.'
                    );
                }
            }
        });
    }
}
