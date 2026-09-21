<?php

namespace App\Http\Requests;

use App\Enums\ComplaintType;
use App\Enums\Location;
use App\Services\GarageContext;
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

        $serviceVehicleRule = Rule::exists('service_vehicles', 'id')->where(fn ($query) => $query
            ->where('garage_id', $garageId)
            ->where('is_active', true)
            ->whereNull('deleted_at'));

        return [
            'bus_id' => ['required', $busRule],
            'yer' => ['required', Rule::in(Location::values())],
            'driver_name' => 'nullable|string|max:255',
            'driver_id' => ['nullable', 'required_if:yer,road', $driverRule],

            // Required for road complaints. The stock service rejects the
            // write if the selected vehicle does not have enough stock —
            // there is no fallback to the warehouse.
            'service_vehicle_id' => ['nullable', 'required_if:yer,road', $serviceVehicleRule],

            'complaint_type' => ['nullable', Rule::in(ComplaintType::values())],

            'complaints' => 'required|array|min:1',
            'complaints.*' => [
                'required',
                'string',
                Rule::when(
                    fn () => in_array($this->input('complaint_type'), [
                        ComplaintType::Accident->value,
                        ComplaintType::Breakdown->value,
                    ], true),
                    Rule::exists('complaint_types', 'name')->where('garage_id', $garageId),
                ),
            ],

            'km' => 'nullable|integer|min:0',
            'status' => 'required|in:pending,in_progress',

            // ── Date & time fields ──
            'reported_date' => ['required_if:yer,road', 'nullable', 'date'],
            'reported_time' => ['required_if:yer,road', 'nullable', 'date_format:H:i'],
            'start_date'    => ['nullable', 'date'],
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_date'      => ['nullable', 'date'],
            'end_time'      => ['nullable', 'date_format:H:i'],

            // ── Parts / details ──
            'details' => 'nullable|array',
            'details.*.code' => 'nullable|string|distinct:strict',
            'details.*.used_quantity' => 'nullable|integer|min:1',
            'details.*.employee_id' => ['required_with:details.*.code', $employeeRule],
            'details.*.notes' => 'required_with:details.*.code|string|max:2000',
            'employee_id' => ['nullable', $employeeRule],

            // ── Service (maintenance) ──
            'service_km' => [
                'nullable',
                'integer',
                'min:0',
                Rule::requiredIf(fn () => $this->input('complaint_type') === ComplaintType::Maintenance->value),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // ── Duplicate part-code guard ──
            //
            // syncDetails() keys details by their `code` value, so two
            // rows sharing the same code silently collapse into one —
            // the second overwrites the first. Reject the payload early
            // with a clear message instead.
            $codes = collect($this->input('details', []))
                ->pluck('code')
                ->filter(fn ($c) => filled($c));

            $duplicates = $codes->duplicates()->unique()->values();

            if ($duplicates->isNotEmpty()) {
                $validator->errors()->add(
                    'details',
                    __('messages.flash.duplicate_part_codes', [
                        'codes' => $duplicates->implode(', '),
                    ])
                );
            }
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
