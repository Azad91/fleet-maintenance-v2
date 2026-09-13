<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'employee_code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'employee_code')->ignore($userId),
            ],
            'pin' => ['nullable', 'string', 'digits_between:4,6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
