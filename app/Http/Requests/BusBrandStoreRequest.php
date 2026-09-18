<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusBrandStoreRequest extends FormRequest
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
        $garageId = GarageContext::getGarageId();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bus_brands', 'name')
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bus_brands', 'code')
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => 'nullable|boolean',
        ];
    }
}
