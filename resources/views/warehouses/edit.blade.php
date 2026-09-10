@extends('layouts.app')

@section('title', __('messages.warehouse.edit'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ {{ __('messages.warehouse.edit') }}</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="code" class="form-label fw-bold">{{ __('messages.warehouse.code') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="code" name="code" required
                       value="{{ old('code', $warehouse->code) }}">
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">{{ __('messages.warehouse.name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="{{ old('name', $warehouse->name) }}">
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label fw-bold">{{ __('messages.warehouse.quantity') }}</label>
                <input type="number" class="form-control" id="quantity" name="quantity"
                       min="0" value="{{ old('quantity', $warehouse->quantity) }}">
            </div>

            <div class="mb-3">
                <label for="unit" class="form-label fw-bold">{{ __('messages.warehouse.unit') }}</label>
                <select class="form-select" id="unit" name="unit">
                    <option value="">{{ __('messages.common.select') }}</option>
                    <option value="piece" {{ $warehouse->unit === 'piece' ? 'selected' : '' }}>{{ __('messages.warehouse.units.piece') }}</option>
                    <option value="liter" {{ $warehouse->unit === 'liter' ? 'selected' : '' }}>{{ __('messages.warehouse.units.liter') }}</option>
                    <option value="meter" {{ $warehouse->unit === 'meter' ? 'selected' : '' }}>{{ __('messages.warehouse.units.meter') }}</option>
                    <option value="kg"    {{ $warehouse->unit === 'kg' ? 'selected' : '' }}>{{ __('messages.warehouse.units.kg') }}</option>
                    <option value="gram"  {{ $warehouse->unit === 'gram' ? 'selected' : '' }}>{{ __('messages.warehouse.units.gram') }}</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-bold">{{ __('messages.warehouse.price_azn') }}</label>
                <input type="number" class="form-control" id="price" name="price"
                       step="0.01" min="0" value="{{ old('price', $warehouse->price) }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> {{ __('messages.common.update') }}
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection