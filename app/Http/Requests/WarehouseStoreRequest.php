<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kod' => ['required', Rule::unique('warehouses', 'kod')->where('garage_id', session('current_garage_id'))->whereNull('deleted_at')],
            'ad' => 'required|string|max:255',
            'miqdar' => 'required|integer|min:0',
            'olcu_vahidi' => 'nullable|string|max:50',
            'qiymet' => 'nullable|numeric|min:0',
        ];
    }
}
