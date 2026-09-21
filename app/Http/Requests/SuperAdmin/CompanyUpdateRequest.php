<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Validation\Rule;

class CompanyUpdateRequest extends SuperAdminRequest
{
    public function rules(): array
    {
        $companyId = $this->route('company')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                // Ignore the current company AND only check non-deleted rows.
                // Order matters: ->ignore() must come before ->whereNull()
                // in Laravel's Rule::unique chain.
                Rule::unique('companies', 'slug')
                    ->ignore($companyId)
                    ->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
