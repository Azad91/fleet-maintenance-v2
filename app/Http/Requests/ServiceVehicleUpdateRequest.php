<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceVehicleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'         => trim((string) $this->input('name')),
            'plate_number' => $this->filled('plate_number')
                ? mb_strtoupper(trim((string) $this->input('plate_number')))
                : null,
            'driver_name'  => $this->filled('driver_name')
                ? trim((string) $this->input('driver_name'))
                : null,
        ]);
    }

    public function rules(): array
    {
        $vehicleId = $this->route('service_vehicle');
        $garageId  = GarageContext::getGarageId();

        return [
            'name' => 'required|string|max:255',
            'plate_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('service_vehicles', 'plate_number')
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at')
                    ->ignore($vehicleId),
            ],
            'driver_name' => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:50',
            'is_active'   => 'nullable|boolean',
            'notes'       => 'nullable|string|max:2000',
        ];
    }
}
