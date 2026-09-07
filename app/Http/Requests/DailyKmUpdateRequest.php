<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyKmUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $garageId = session('current_garage_id');
        $recordId = $this->route('daily_km_record');

        return [
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', $garageId)],
            'date' => 'required|date',
            'km' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'bus_id.required' => 'Avtobus seçilməlidir.',
            'bus_id.exists' => 'Seçilən avtobus mövcud deyil.',
            'date.required' => 'Tarix daxil edilməlidir.',
            'date.date' => 'Tarix formatı düzgün deyil.',
            'km.required' => 'KM daxil edilməlidir.',
            'km.integer' => 'KM tam ədəd olmalıdır.',
            'km.min' => 'KM 0-dan kiçik ola bilməz.',
            'notes.max' => 'Qeyd 1000 simvoldan çox ola bilməz.',
        ];
    }
}
