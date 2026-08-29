<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bus_id' => 'required|exists:buses,id',
            'yer' => 'required|in:yol,qaraj',
            'surucu_adi' => 'nullable|string|max:255',
            'shikayet' => 'required|array|min:1',
            'shikayet.*' => 'required|string',
            'km' => 'nullable|integer|min:0',
            'status' => 'required|in:gözləmədə,işdə,həll olundu',
            'is_bitme_tarix' => 'required_if:status,həll olundu|nullable|date',
            'is_bitme_saat' => 'required_if:status,həll olundu|nullable|date_format:H:i',
            'sikayet_tipi' => 'nullable|in:qezali,nasazliq,texniki_xidmet',
            'detallar' => 'nullable|array',
            'detallar.*.kodu' => 'nullable|string',
            'detallar.*.islenen_miqdar' => 'nullable|integer|min:1',
            'detallar.*.employee_id' => 'required_with:detallar.*.kodu|exists:employees,id',
            'detallar.*.qeyd' => 'required_with:detallar.*.kodu|string|max:2000',
            'employee_id' => 'nullable|exists:employees,id',
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
            'shikayet.required' => 'Ən azı bir şikayət daxil edilməlidir.',
            'shikayet.array' => 'Şikayət array formatında olmalıdır.',
            'shikayet.*.required' => 'Hər şikayət boş ola bilməz.',
            'sikayet_tipi.in' => 'Şikayət tipi düzgün seçilməyib.',
            'status.required' => 'Status seçilməlidir.',
            'status.in' => 'Status düzgün seçilməyib.',
            'km.integer' => 'KM tam ədəd olmalıdır.',
            'km.min' => 'KM 0-dan kiçik ola bilməz.',
            'employee_id.exists' => 'Seçilən işçi mövcud deyil.',
            'detallar.*.employee_id.exists' => 'Detal üçün seçilən işçi mövcud deyil.',
            'detallar.*.employee_id.required_with' => 'Hər detal üçün işi görən işçi seçilməlidir.',
            'service_template_id.exists' => 'Seçilən servis şablonu mövcud deyil.',
            'service_km.required_if' => 'Servis şablonu seçilibsə, servis km-i məcburidir!',
            'service_km.integer' => 'Servis km-i tam ədəd olmalıdır.',
            'service_km.min' => 'Servis km-i 0-dan kiçik ola bilməz.',
            'is_bitme_tarix.required_if' => 'Şikayət "həll olundu" statusuna keçərsə, bitmə tarixi məcburidir!',
            'is_bitme_saat.required_if' => 'Şikayət "həll olundu" statusuna keçərsə, bitmə saatı məcburidir!',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('surucu_adi', 'required|string|max:255', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->sometimes('bildirilme_tarix', 'required|date', function ($input) {
            return $input->yer == 'yol';
        });

        $validator->sometimes('bildirilme_saat', 'required|date_format:H:i', function ($input) {
            return $input->yer == 'yol';
        });
    }
}
