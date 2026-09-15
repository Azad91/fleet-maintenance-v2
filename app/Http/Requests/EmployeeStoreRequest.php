<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    public function rules(): array
    {
        $garageId = GarageContext::getGarageId();

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('employees', 'code')->where(fn ($query) => $query
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at')),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
    }
}
