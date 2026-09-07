<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Ad daxil edilməlidir.',
            'first_name.max' => 'Ad 255 simvoldan çox ola bilməz.',
            'last_name.required' => 'Soyad daxil edilməlidir.',
            'last_name.max' => 'Soyad 255 simvoldan çox ola bilməz.',
            'position.required' => 'Vəzifə seçilməlidir.',
            'position.max' => 'Vəzifə 255 simvoldan çox ola bilməz.',
            'notes.max' => 'Qeyd 1000 simvoldan çox ola bilməz.',
            'is_active.boolean' => 'Aktiv sahəsi doğru və ya yanlış olmalıdır.',
        ];
    }
}
