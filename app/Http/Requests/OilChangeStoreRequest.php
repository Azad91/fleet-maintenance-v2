<?php

namespace App\Http\Requests;

use App\Enums\OilType;
use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OilChangeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $garageId = GarageContext::getGarageId();

        return [
            'bus_id' => [
                'required',
                Rule::exists('buses', 'id')->where('garage_id', $garageId),
            ],
            'oil_type' => ['required', Rule::in(OilType::values())],
            'oil_brand' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(fn () => $this->input('oil_type') === OilType::Gearbox->value),
            ],
            'scheduled_km' => 'nullable|integer|min:0',
            'actual_km'    => 'required|integer|min:0',
            'changed_at'   => 'nullable|date',
            'notes'        => 'nullable|string|max:2000',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('oil_brand')) {
            $this->merge([
                'oil_brand' => mb_strtoupper(trim((string) $this->input('oil_brand'))),
            ]);
        }
    }
}
