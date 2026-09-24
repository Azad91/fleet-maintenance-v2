<?php

namespace App\Http\Requests\Concerns;

use App\Enums\ComplaintType;
use App\Enums\Location;
use App\Services\GarageContext;
use Illuminate\Validation\Rule;

/**
 * Shared validation rules for creating and updating complaints.
 *
 * WHY THIS TRAIT EXISTS
 * ---------------------
 * ComplaintStoreRequest and ComplaintUpdateRequest used to declare
 * the exact same rules() and withValidator() bodies — 100+ lines of
 * duplicated logic. Any change to a rule (e.g. adding a new field,
 * tightening a max length) had to be made in two places, and a
 * missed update would silently let invalid data through one path.
 *
 * The trait centralizes the rules. The two Request classes now only
 * differ in their class name and — when the authorization logic
 * eventually diverges — their authorize() method.
 *
 * RULE DESIGN NOTES
 * -----------------
 * - `bus_id`, `driver_id`, `service_vehicle_id`, `employee_id` and
 *   `details.*.employee_id` are all scoped to the CURRENT garage via
 *   Rule::exists(...)->where('garage_id', ...). This makes it
 *   impossible to reference a row from another tenant, even if the
 *   caller tampers with the form payload.
 *
 * - `details.*.used_quantity` allows min:0. Zero means "inspected /
 *   repaired, nothing consumed" — see migration
 *   2026_09_17_174409 and ComplaintStockService::deductStock().
 *
 * - `service_km` is required ONLY when the complaint type is
 *   `maintenance`. A maintenance complaint without an interval value
 *   is meaningless.
 */
trait ValidatesComplaint
{
    /**
     * The complete rule set for a complaint store / update payload.
     *
     * @return array<string, mixed>
     */
    protected function complaintRules(): array
    {
        $garageId = GarageContext::getGarageId();

        $busRule = Rule::exists('buses', 'id')
            ->where('garage_id', $garageId);

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

            // Required for road complaints. The stock service rejects
            // the write if the selected vehicle does not have enough
            // stock — there is no fallback to the warehouse.
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
            'start_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],

            // ── Parts / details ──
            'details' => 'nullable|array',
            'details.*.code' => 'nullable|string|distinct:strict',
            // 0 is allowed and means "inspected / repaired, nothing
            // consumed". See the trait docblock for the rationale.
            'details.*.used_quantity' => 'nullable|integer|min:0',
            'details.*.employee_id' => ['required_with:details.*.code', $employeeRule],
            'details.*.notes' => 'required_with:details.*.code|string|max:2000',
            'employee_id' => ['nullable', $employeeRule],

            // ── Service (maintenance) ──
            'service_km' => [
                'nullable',
                'integer',
                'min:0',
                Rule::requiredIf(
                    fn () => $this->input('complaint_type') === ComplaintType::Maintenance->value
                ),
            ],
        ];
    }

    /**
     * Cross-field validation that cannot be expressed with simple
     * rule strings.
     *
     * TWO GUARDS
     * ----------
     * 1. Duplicate part codes.
     *    ComplaintService::syncDetails() keys details by their `code`
     *    value. Two rows sharing the same code would silently
     *    collapse into one — the second overwrites the first. We
     *    reject the payload early with a clear message.
     *
     * 2. Required fields on part rows.
     *    `details.*.employee_id` and `details.*.notes` are declared
     *    with `required_with:details.*.code`, but Laravel's
     *    required_with checks for the PRESENCE of the sibling key,
     *    not its truthiness. An empty-string code slips past the
     *    rule. The explicit loop below closes that gap.
     */
    protected function validateComplaintDetails($validator): void
    {
        $validator->after(function ($validator) {
            // ── Duplicate part-code guard ──
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

            // ── Per-row required fields ──
            foreach ($this->input('details', []) as $index => $detail) {
                if (blank($detail['code'] ?? null)) {
                    continue;
                }

                if (blank($detail['employee_id'] ?? null)) {
                    $validator->errors()->add(
                        "details.$index.employee_id",
                        __('validation.required', [
                            'attribute' => __('validation.attributes.employee_id'),
                        ])
                    );
                }

                if (blank($detail['notes'] ?? null)) {
                    $validator->errors()->add(
                        "details.$index.notes",
                        __('validation.required', [
                            'attribute' => __('validation.attributes.notes'),
                        ])
                    );
                }
            }
        });
    }
}
