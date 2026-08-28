<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bus_project' => 'nullable|string|max:255',
            'vin' => 'nullable|string|max:17',
            'uzunluq' => 'nullable|numeric|min:0',
            'xett_no' => 'nullable|string|max:255',
            'dqn' => 'required|unique:buses,dqn',
            'motor_no' => 'nullable|string|max:255',
            'km' => 'nullable|integer|min:0',
            'garage_id' => 'nullable|exists:garages,id',  // 🔥 ƏLAVƏ
            'company_id' => 'nullable|exists:companies,id', // 🔥 ƏLAVƏ
        ];
    }
}
