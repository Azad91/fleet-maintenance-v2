<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
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
        $garageId = GarageContext::getGarageId();

        return [
            'bus_id' => [
                'required',
                Rule::exists('buses', 'id')->where('garage_id', $garageId),
            ],
            'date'  => 'required|date',
            'km'    => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
