<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class CompanyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // ─── Company fields ───
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:companies,slug',
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],

            // ─── First Director (required — see SuperAdmin spec) ───
            'director_name' => ['required', 'string', 'max:255'],
            'director_email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'director_password' => ['required', 'string', 'min:8', 'confirmed'],
            'director_pin' => [
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
            'director_name.required' => __('messages.super_admin.companies.director_name_required'),
            'director_email.required' => __('messages.super_admin.companies.director_email_required'),
            'director_email.unique' => __('messages.super_admin.companies.director_email_taken'),
            'director_password.required' => __('messages.super_admin.companies.director_password_required'),
            'director_pin.required' => __('messages.super_admin.companies.director_pin_required'),
            'director_pin.digits_between' => __('messages.super_admin.companies.director_pin_digits'),
            'director_pin.confirmed' => __('messages.super_admin.companies.director_pin_mismatch'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize whitespace on director fields
        $this->merge([
            'director_name' => trim((string) $this->input('director_name')),
            'director_email' => mb_strtolower(trim((string) $this->input('director_email'))),
        ]);
    }
}
