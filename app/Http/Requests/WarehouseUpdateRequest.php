<?php

namespace App\Http\Requests;

use App\Services\GarageContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse');
        $garageId    = GarageContext::getGarageId();

        return [
            'code' => [
                'required',
                Rule::unique('warehouses', 'code')
                    ->where('garage_id', $garageId)
                    ->whereNull('deleted_at')
                    ->ignore($warehouseId),
            ],
            'name'              => 'required|string|max:255',
            'category'          => 'nullable|string|max:255',
            'quantity'          => 'required|integer|min:0',
            'minimum_quantity'  => 'nullable|integer|min:0',
            'unit'              => 'nullable|string|max:50',
            'price'             => 'nullable|numeric|min:0',
            'supplier'          => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:2000',
        ];
    }
}
