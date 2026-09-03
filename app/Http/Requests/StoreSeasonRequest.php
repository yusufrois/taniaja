<?php

namespace App\Http\Requests;

use App\Models\Season;
use App\Models\Variety;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Season::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'greenhouse_id' => [
                'required',
                Rule::exists('greenhouses', 'id')->where('company_id', $companyId),
            ],
            'crop_id' => [
                'required',
                Rule::exists('crops', 'id')->where('company_id', $companyId),
            ],
            'variety_id' => [
                'required',
                Rule::exists('varieties', 'id')->where('company_id', $companyId),
            ],
            'season_name' => ['required', 'string', 'max:255'],
            'planting_date' => ['required', 'date'],
            'estimated_harvest_date' => ['nullable', 'date', 'after_or_equal:planting_date'],
            'actual_harvest_date' => ['nullable', 'date', 'after_or_equal:planting_date'],
            'plant_count' => ['nullable', 'integer', 'min:0'],
            'target_yield' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['planning', 'active', 'harvesting', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'estimated_harvest_date.after_or_equal' => 'Estimasi panen tidak boleh sebelum tanggal tanam.',
            'actual_harvest_date.after_or_equal' => 'Tanggal panen aktual tidak boleh sebelum tanggal tanam.',
        ];
    }

    /**
     * Cross-field checks that Laravel's declarative rules() can't express
     * on their own — kept here rather than pushed into the controller so
     * the invalid request still gets rejected as a clean 422, matching
     * the rest of this app's validation behaviour.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cropId = $this->input('crop_id');
            $varietyId = $this->input('variety_id');

            if ($cropId && $varietyId) {
                $varietyBelongsToCrop = Variety::where('id', $varietyId)
                    ->where('crop_id', $cropId)
                    ->exists();

                if (! $varietyBelongsToCrop) {
                    $validator->errors()->add(
                        'variety_id',
                        'Varietas yang dipilih tidak termasuk dalam crop yang dipilih.'
                    );
                }
            }

            $greenhouseId = $this->input('greenhouse_id');
            $requestedStatus = $this->input('status', 'planning');

            if ($greenhouseId && in_array($requestedStatus, ['planning', 'active', 'harvesting'], true)) {
                $hasRunningSeason = Season::where('greenhouse_id', $greenhouseId)
                    ->whereIn('status', ['planning', 'active', 'harvesting'])
                    ->exists();

                if ($hasRunningSeason) {
                    $validator->errors()->add(
                        'greenhouse_id',
                        'Greenhouse ini sudah memiliki musim tanam yang masih berjalan. '.
                        'Selesaikan atau batalkan musim sebelumnya dahulu.'
                    );
                }
            }
        });
    }
}
