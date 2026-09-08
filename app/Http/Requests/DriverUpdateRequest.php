<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => $this->filled('last_name') ? trim((string) $this->input('last_name')) : null,
        ]);
    }

    public function rules(): array
    {
        $driverId = $this->route('driver');
        $garageId = GarageContext::getGarageId();

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('drivers', 'code')->ignore($driverId)->where(fn ($query) => $query
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at')),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Bu sürücü kodu seçilmiş qarajda artıq mövcuddur.',
        ];
    }
}
