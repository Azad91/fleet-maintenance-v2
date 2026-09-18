@extends('layouts.app')

@section('title', __('messages.motor_oil.import_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.motor_oil.import_title') }}</h4>
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

        <form action="{{ route('motor-oil.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>{{ __('messages.complaints.import_format_title') }}:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>part_name</strong> — {{ __('messages.motor_oil.part_name') }}</li>
                    <li><strong>unit</strong> — {{ __('messages.motor_oil.unit') }}</li>
                    <li><strong>quantity</strong> — {{ __('messages.motor_oil.quantity') }}</li>
                    <li><strong>part_code</strong> — {{ __('messages.motor_oil.part_code') }}</li>
                    <li><strong>36000, 72000, ...</strong> — {{ __('messages.motor_oil.km_columns') }}</li>
                </ul>
            </div>

            {{-- ─── Brand selection (REQUIRED) ─── --}}
            <div class="card mb-3" style="border: 1px solid #bfdbfe; background: #eff6ff;">
                <div class="card-body">
                    <label for="brand_id" class="form-label fw-bold">
                        <i class="bi bi-tag"></i> {{ __('messages.motor_oil.brand') }}
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="brand_id" name="brand_id" required>
                        <option value="">{{ __('messages.common.select') }}</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">
                        {{ __('messages.motor_oil.import_brand_hint') }}
                    </small>
                    @if($brands->isEmpty())
                        <div class="alert alert-warning mt-2 mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            {{ __('messages.motor_oil.no_brands_hint') }}
                            — <a href="{{ route('bus-brands.create') }}">{{ __('messages.bus_brands.new') }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">{{ __('messages.buses.import_select_file') }}</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <button type="submit" class="btn btn-success" {{ $brands->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-upload"></i> {{ __('messages.buses.import_button') }}
            </button>
            <a href="{{ route('motor-oil.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
        </form>
    </div>
</div>
@endsection
