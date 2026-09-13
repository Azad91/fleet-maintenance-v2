<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use App\Enums\ComplaintType;
use App\Enums\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $garageId = GarageContext::getGarageId();

        $busRule = Rule::exists('buses', 'id')->where('garage_id', $garageId);

        $employeeRule = Rule::exists('employees', 'id')
            ->where('garage_id', $garageId);

        $driverRule = Rule::exists('drivers', 'id')->where(fn ($query) => $query
            ->where('garage_id', $garageId)
            ->where('is_active', true)
            ->whereNull('deleted_at'));

        return [
            'bus_id'      => ['required', $busRule],
            'yer'         => ['required', Rule::in(Location::values())],
            'driver_name' => 'nullable|string|max:255',
            'driver_id'   => ['nullable', 'required_if:yer,road', $driverRule],

            // complaint_type is a CATEGORY enum (accident/breakdown/maintenance),
            // NOT a specific type from the complaint_types table.
            // The valid values are defined in App\Enums\ComplaintType.
            'complaint_type' => ['nullable', Rule::in(ComplaintType::values())],

            // complaint items are descriptions that should exist in complaint_types
            // for the CURRENT garage.
            'complaints'      => 'required|array|min:1',
            'complaints.*'    => [
                'required',
                'string',
                Rule::exists('complaint_types', 'name')->where('garage_id', $garageId),
            ],

            'km'             => 'nullable|integer|min:0',
            'status'         => 'required|in:pending,in_progress',
            'details'                     => 'nullable|array',
            'details.*.code'              => 'nullable|string',
            'details.*.used_quantity'     => 'nullable|integer|min:1',
            'details.*.employee_id'       => ['required_with:details.*.code', $employeeRule],
            'details.*.notes'             => 'required_with:details.*.code|string|max:2000',
            'employee_id' => ['nullable', $employeeRule],
            'service_template_id' => 'nullable|exists:service_templates,id',
            'service_km'          => 'required_if:service_template_id,!null|nullable|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('reported_date', 'required|date', function ($input) {
            return $input->yer === 'road';
        });

        $validator->sometimes('reported_time', 'required|date_format:H:i', function ($input) {
            return $input->yer === 'road';
        });

        $validator->after(function ($validator) {
            foreach ($this->input('details', []) as $index => $detail) {
                if (blank($detail['code'] ?? null)) {
                    continue;
                }

                if (blank($detail['employee_id'] ?? null)) {
                    $validator->errors()->add(
                        "details.$index.employee_id",
                        __('validation.required', ['attribute' => __('validation.attributes.employee_id')])
                    );
                }

                if (blank($detail['notes'] ?? null)) {
                    $validator->errors()->add(
                        "details.$index.notes",
                        __('validation.required', ['attribute' => __('validation.attributes.notes')])
                    );
                }
            }
        });
    }
}
