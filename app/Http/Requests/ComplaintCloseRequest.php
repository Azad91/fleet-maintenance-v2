<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => 'required|date',
            'end_time' => 'required|date_format:H:i',
            'work_done' => 'required|string|min:5',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.required' => 'Bitmə tarixi daxil edilməlidir.',
            'end_date.date' => 'Bitmə tarixi düzgün formatda deyil.',
            'end_time.required' => 'Bitmə saatı daxil edilməlidir.',
            'end_time.date_format' => 'Bitmə saatı HH:MM formatında olmalıdır.',
            'work_done.required' => 'Görülən işlər daxil edilməlidir.',
            'work_done.min' => 'Görülən işlər ən azı 5 simvol olmalıdır.',
        ];
    }
}