<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $garageId = GarageContext::getGarageId();
        $busRule = Rule::exists('buses', 'id')->where('garage_id', $garageId);
        $employeeRule = Rule::exists('employees', 'id')->where('garage_id', $garageId);
        $driverRule = Rule::exists('drivers', 'id')->where(fn ($query) => $query
            ->where('garage_id', $garageId)
            ->where('is_active', true)
            ->whereNull('deleted_at'));

        return [
            'bus_id' => ['required', $busRule],
            'yer' => 'required|in:yol,qaraj',
            'driver_name' => 'nullable|string|max:255',
            'driver_id' => ['nullable', 'required_if:yer,yol', $driverRule],
            'complaints' => 'required|array|min:1',
            'complaints.*' => 'required|string',
            'km' => 'nullable|integer|min:0',
            'status' => 'required|in:gözləmədə,işdə',
            'complaint_type' => 'nullable|exists:complaint_types,name',
            'details' => 'nullable|array',
            'details.*.code' => 'nullable|string',
            'details.*.used_quantity' => 'nullable|integer|min:1',
            'details.*.employee_id' => ['required_with:details.*.code', $employeeRule],
            'details.*.notes' => 'required_with:details.*.code|string|max:2000',
            'employee_id' => ['nullable', $employeeRule],
            'service_template_id' => 'nullable|exists:service_templates,id',
            'service_km' => 'required_if:service_template_id,!null|nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'bus_id.required' => 'Avtobus seçilməlidir.',
            'bus_id.exists' => 'Seçilən avtobus mövcud deyil.',
            'yer.required' => 'Yer seçilməlidir.',
            'yer.in' => 'Yer yalnız "yol" və ya "qaraj" ola bilər.',
            'driver_id.required_if' => 'Yol üçün aktiv sürücü seçilməlidir.',
            'driver_id.exists' => 'Sürücü tapılmadı və ya cari qaraja aid deyil.',
            'complaints.required' => 'Ən azı bir şikayət daxil edilməlidir.',
            'complaints.array' => 'Şikayət array formatında olmalıdır.',
            'complaints.*.required' => 'Hər şikayət boş ola bilməz.',
            'complaint_type.exists' => 'Seçilən şikayət tipi düzgün deyil.',
            'status.required' => 'Status seçilməlidir.',
            'status.in' => 'Kartı bağlamaq üçün ayrıca bağlama əməliyyatından istifadə edin.',
            'km.integer' => 'KM tam ədəd olmalıdır.',
            'km.min' => 'KM 0-dan kiçik ola bilməz.',
            'employee_id.exists' => 'Seçilən işçi mövcud deyil.',
            'details.*.employee_id.exists' => 'Detal üçün seçilən işçi mövcud deyil.',
            'details.*.employee_id.required_with' => 'Hər detal üçün işi görən işçi seçilməlidir.',
            'service_km.required_if' => 'Servis şablonu seçilibsə, servis km-i məcburidir!',
            'service_km.integer' => 'Servis km-i tam ədəd olmalıdır.',
            'service_km.min' => 'Servis km-i 0-dan kiçik ola bilməz.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('driver_name', 'required|string|max:255', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->sometimes('reported_date', 'required|date', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->sometimes('reported_time', 'required|date_format:H:i', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->after(function ($validator) {
            foreach ($this->input('details', []) as $index => $detail) {
                if (blank($detail['code'] ?? null)) {
                    continue;
                }

                if (blank($detail['employee_id'] ?? null)) {
                    $validator->errors()->add("details.$index.employee_id", 'Hər detal üçün işi görən işçi seçilməlidir.');
                }
                if (blank($detail['notes'] ?? null)) {
                    $validator->errors()->add("details.$index.notes", 'Hər detal üçün görülən iş yazılmalıdır.');
                }
            }
        });
    }
}
