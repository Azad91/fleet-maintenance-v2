<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Validation\Rule;

class UserUpdateRequest extends SuperAdminRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->whereNull('deleted_at'),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'employee_code')
                    ->ignore($userId)
                    ->whereNull('deleted_at'),
            ],
            'pin' => ['nullable', 'string', 'digits_between:4,6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}