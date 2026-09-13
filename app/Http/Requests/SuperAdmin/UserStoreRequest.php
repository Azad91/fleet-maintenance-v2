<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_code' => ['nullable', 'string', 'max:50', 'unique:users,employee_code'],
            'pin' => ['nullable', 'string', 'digits_between:4,6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
