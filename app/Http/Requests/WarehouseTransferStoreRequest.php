<?php

namespace App\Http\Requests;

use App\Enums\TransferType;
use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseTransferStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $garageId = GarageContext::getGarageId();

        return [
            'to_garage_id' => [
                'nullable',
                'integer',
                'exists:garages,id',
                Rule::notIn([$garageId]), // Can't transfer to self
            ],
            'to_service_vehicle_id' => [
                'nullable',
                'integer',
                'exists:service_vehicles,id',
            ],
            'type'  => ['required', Rule::in(TransferType::values())],
            'notes' => 'nullable|string|max:2000',

            'items'                    => 'required|array|min:1',
            'items.*.warehouse_id'     => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('garage_id', $garageId),
            ],
            'items.*.declared_quantity' => 'required|integer|min:1',
            'items.*.notes'             => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');

            if ($type === TransferType::GarageToGarage->value && ! $this->input('to_garage_id')) {
                $validator->errors()->add('to_garage_id', __('validation.required', [
                    'attribute' => __('messages.transfers.to_garage'),
                ]));
            }

            if ($type === TransferType::ToServiceVehicle->value && ! $this->input('to_service_vehicle_id')) {
                $validator->errors()->add('to_service_vehicle_id', __('validation.required', [
                    'attribute' => __('messages.transfers.to_service_vehicle'),
                ]));
            }

            // return_to_quarantine needs no destination.
        });
    }
}
