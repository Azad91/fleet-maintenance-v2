<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GarageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // ─── Garage fields ───
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
            'required',
            'string',
            'max:50',
            // Only enforce uniqueness against NON-deleted rows, matching
            // the partial unique index garages_code_active_unique.
            Rule::unique('garages', 'code')->whereNull('deleted_at'),
        ],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],

            // ─── First Garage Admin (required — see SuperAdmin spec) ───
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'admin_pin' => [
                'required',
                'string',
                'digits_between:4,6',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_name.required' => __('messages.super_admin.garages.admin_name_required'),
            'admin_email.required' => __('messages.super_admin.garages.admin_email_required'),
            'admin_email.unique' => __('messages.super_admin.garages.admin_email_taken'),
            'admin_password.required' => __('messages.super_admin.garages.admin_password_required'),
            'admin_pin.required' => __('messages.super_admin.garages.admin_pin_required'),
            'admin_pin.digits_between' => __('messages.super_admin.garages.admin_pin_digits'),
            'admin_pin.confirmed' => __('messages.super_admin.garages.admin_pin_mismatch'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize whitespace on admin fields
        $this->merge([
            'admin_name' => trim((string) $this->input('admin_name')),
            'admin_email' => mb_strtolower(trim((string) $this->input('admin_email'))),
        ]);
    }
}
