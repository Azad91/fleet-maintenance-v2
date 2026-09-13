<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GarageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $garageId = $this->route('garage')->id;

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'code'       => [
                'required',
                'string',
                'max:50',
                Rule::unique('garages', 'code')->ignore($garageId),
            ],
            'address'    => ['nullable', 'string', 'max:1000'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }
}
