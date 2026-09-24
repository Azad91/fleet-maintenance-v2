<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for updating a bus brand.
 *
 * Mirrors BusBrandStoreRequest but adds ->ignore() so the current
 * brand can keep its own name/code without triggering a uniqueness
 * violation.
 */
class BusBrandUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    public function rules(): array
    {
        $brandId = $this->route('busBrand');
        $garageId = GarageContext::getGarageId();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bus_brands', 'name')
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at')
                    ->ignore($brandId),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bus_brands', 'code')
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at')
                    ->ignore($brandId),
            ],
            'is_active' => 'nullable|boolean',
        ];
    }
}
