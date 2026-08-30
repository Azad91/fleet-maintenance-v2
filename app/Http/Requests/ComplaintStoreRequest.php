<?php

namespace App\Http\Requests;

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
        $garageId = session('current_garage_id');
        $busRule = Rule::exists('buses', 'id')->where('garage_id', $garageId);
        $employeeRule = Rule::exists('employees', 'id')->where('garage_id', $garageId);
        $driverRule = Rule::exists('drivers', 'id')->where(fn ($query) => $query
            ->where('garage_id', $garageId)
            ->where('aktiv', true)
            ->whereNull('deleted_at'));

        return [
            'bus_id' => ['required', $busRule],
            'yer' => 'required|in:yol,qaraj',
            'surucu_adi' => 'nullable|string|max:255',
            'driver_id' => ['nullable', 'required_if:yer,yol', $driverRule],
            'shikayet' => 'required|array|min:1',
            'shikayet.*' => 'required|string',
            'km' => 'nullable|integer|min:0',
            'status' => 'required|in:gözləmədə,işdə',
            'sikayet_tipi' => 'nullable|in:qezali,nasazliq,texniki_xidmet',
            'detallar' => 'nullable|array',
            'detallar.*.kodu' => 'nullable|string',
            'detallar.*.islenen_miqdar' => 'nullable|integer|min:1',
            'detallar.*.employee_id' => ['required_with:detallar.*.kodu', $employeeRule],
            'detallar.*.qeyd' => 'required_with:detallar.*.kodu|string|max:2000',
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
            'driver_id.required_if' => 'Yol üçün aktiv sürücü kodu seçilməlidir.',
            'driver_id.exists' => 'Sürücü kodu tapılmadı və ya cari qaraja aid deyil.',
            'shikayet.required' => 'Ən azı bir şikayət daxil edilməlidir.',
            'shikayet.array' => 'Şikayət array formatında olmalıdır.',
            'shikayet.*.required' => 'Hər şikayət boş ola bilməz.',
            'sikayet_tipi.in' => 'Şikayət tipi düzgün seçilməyib.',
            'status.required' => 'Status seçilməlidir.',
            'status.in' => 'Yeni kart yalnız "gözləmədə" və ya "işdə" statusunda açıla bilər. Kartı bağlamaq üçün ayrıca bağlama əməliyyatından istifadə edin.',
            'km.integer' => 'KM tam ədəd olmalıdır.',
            'km.min' => 'KM 0-dan kiçik ola bilməz.',
            'employee_id.exists' => 'Seçilən işçi mövcud deyil.',
            'detallar.*.employee_id.exists' => 'Detal üçün seçilən işçi mövcud deyil.',
            'detallar.*.employee_id.required_with' => 'Hər detal üçün işi görən işçi seçilməlidir.',
            'service_template_id.exists' => 'Seçilən servis şablonu mövcud deyil.',
            'service_km.required_if' => 'Servis şablonu seçilibsə, servis km-i məcburidir!',
            'service_km.integer' => 'Servis km-i tam ədəd olmalıdır.',
            'service_km.min' => 'Servis km-i 0-dan kiçik ola bilməz.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('bildirilme_tarix', 'required|date', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->sometimes('bildirilme_saat', 'required|date_format:H:i', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->after(function ($validator) {
            foreach ($this->input('detallar', []) as $index => $detal) {
                if (blank($detal['kodu'] ?? null)) {
                    continue;
                }

                if (blank($detal['employee_id'] ?? null)) {
                    $validator->errors()->add("detallar.$index.employee_id", 'Hər detal üçün işi görən işçi seçilməlidir.');
                }
                if (blank($detal['qeyd'] ?? null)) {
                    $validator->errors()->add("detallar.$index.qeyd", 'Hər detal üçün görülən iş yazılmalıdır.');
                }
            }
        });
    }
}
