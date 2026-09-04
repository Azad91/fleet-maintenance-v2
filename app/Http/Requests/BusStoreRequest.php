<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'route_number' => [  // əvvəl: xett_no
                'nullable',
                'string',
                'max:255',
                Rule::unique('buses', 'route_number')
                    ->where('garage_id', session('current_garage_id'))
                    ->whereNull('deleted_at')
            ],
            'dqn' => ['required', Rule::unique('buses', 'dqn')->where('garage_id', session('current_garage_id'))->whereNull('deleted_at')],
            'engine_number' => 'nullable|string|max:255', // əvvəl: motor_no
            'km' => 'nullable|integer|min:0',
        ];
    }
}
