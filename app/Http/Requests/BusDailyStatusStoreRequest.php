<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusDailyStatusStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $garageId = GarageContext::getGarageId();

        return [
            'bus_id' => ['required', Rule::exists('buses', 'id')->where('garage_id', $garageId)],
            'date' => 'required|date',
            'status' => 'required|string|max:255',
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
            'status.required' => 'Status daxil edilməlidir.',
            'status.max' => 'Status 255 simvoldan çox ola bilməz.',
            'notes.max' => 'Qeyd 1000 simvoldan çox ola bilməz.',
        ];
    }
}