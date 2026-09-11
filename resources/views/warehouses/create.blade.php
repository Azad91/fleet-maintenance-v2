@extends('layouts.app')

@section('title', __('messages.warehouse.new'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📦 {{ __('messages.warehouse.new') }}</h4>
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

        <form action="{{ route('warehouses.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="code" class="form-label fw-bold">{{ __('messages.warehouse.code') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="code" name="code" required
                       placeholder="{{ __('messages.warehouse.code_placeholder') }}"
                       value="{{ old('code') }}">
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">{{ __('messages.warehouse.name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required
                       placeholder="{{ __('messages.warehouse.name_placeholder') }}"
                       value="{{ old('name') }}">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="category" class="form-label fw-bold">{{ __('messages.warehouse.category') }}</label>
                    <input type="text" class="form-control" id="category" name="category"
                           value="{{ old('category') }}" maxlength="255">
                </div>

                <div class="col-md-6">
                    <label for="unit" class="form-label fw-bold">{{ __('messages.warehouse.unit') }}</label>
                    <select class="form-select" id="unit" name="unit">
                        <option value="">{{ __('messages.common.select') }}</option>
                        <option value="piece" {{ old('unit') === 'piece' ? 'selected' : '' }}>{{ __('messages.warehouse.units.piece') }}</option>
                        <option value="liter" {{ old('unit') === 'liter' ? 'selected' : '' }}>{{ __('messages.warehouse.units.liter') }}</option>
                        <option value="meter" {{ old('unit') === 'meter' ? 'selected' : '' }}>{{ __('messages.warehouse.units.meter') }}</option>
                        <option value="kg"    {{ old('unit') === 'kg' ? 'selected' : '' }}>{{ __('messages.warehouse.units.kg') }}</option>
                        <option value="gram"  {{ old('unit') === 'gram' ? 'selected' : '' }}>{{ __('messages.warehouse.units.gram') }}</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="quantity" class="form-label fw-bold">{{ __('messages.warehouse.quantity') }}</label>
                    <input type="number" class="form-control" id="quantity" name="quantity"
                           min="0" value="{{ old('quantity', 0) }}" placeholder="0">
                </div>

                <div class="col-md-6">
                    <label for="minimum_quantity" class="form-label fw-bold">{{ __('messages.warehouse.minimum_quantity') }}</label>
                    <input type="number" class="form-control" id="minimum_quantity" name="minimum_quantity"
                           min="0" value="{{ old('minimum_quantity', 0) }}">
                </div>

                <div class="col-md-6">
                    <label for="price" class="form-label fw-bold">{{ __('messages.warehouse.price_azn') }}</label>
                    <input type="number" class="form-control" id="price" name="price"
                           step="0.01" min="0" placeholder="{{ __('messages.warehouse.price_placeholder') }}"
                           value="{{ old('price') }}">
                    <small class="text-muted">{{ __('messages.warehouse.price_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label for="supplier" class="form-label fw-bold">{{ __('messages.warehouse.supplier') }}</label>
                    <input type="text" class="form-control" id="supplier" name="supplier"
                           value="{{ old('supplier') }}" maxlength="255">
                </div>

                <div class="col-md-12">
                    <label for="notes" class="form-label fw-bold">📝 {{ __('messages.common.notes') }}</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
