<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Validation\Rule;

class UserStoreRequest extends SuperAdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                // Only check active rows — the partial unique index
                // users_email_active_unique allows reuse after
                // soft-delete. Mirror that rule here so the operator
                // does not get a false "already taken" error.
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'employee_code')->whereNull('deleted_at'),
            ],
            'pin' => ['nullable', 'string', 'digits_between:4,6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
