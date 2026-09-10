<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusDailyStatusUpdateRequest extends FormRequest
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
            'date'   => 'required|date',
            'status' => 'required|string|max:255',
            'notes'  => 'nullable|string|max:1000',
        ];
    }
}
