<?php

namespace App\Http\Requests;

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

        return [
            'code' => ['required', Rule::unique('warehouses', 'code')->where('garage_id', session('current_garage_id'))->whereNull('deleted_at')->ignore($warehouseId)],
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
        ];
    }
}
