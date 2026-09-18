@extends('layouts.app')

@section('title', __('messages.buses.import_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.buses.import_title') }}</h4>
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

        <form action="{{ route('buses.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>{{ __('messages.buses.import_format_title') }}:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>BUS PROJECT</strong> – {{ __('messages.buses.import_col_project') }}</li>
                    <li><strong>VIN</strong> – {{ __('messages.buses.import_col_vin') }}</li>
                    <li><strong>UZUNLUQ</strong> – {{ __('messages.buses.import_col_length') }}</li>
                    <li><strong>Xətt №</strong> – {{ __('messages.buses.import_col_route') }}</li>
                    <li><strong>DQN</strong> – {{ __('messages.buses.import_col_dqn') }} <span class="text-danger">*</span></li>
                    <li><strong>MOTOR №</strong> – {{ __('messages.buses.import_col_engine') }}</li>
                </ul>
                <p class="mt-2 mb-0 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>#</strong> {{ __('messages.buses.import_note_auto') }}
                </p>
            </div>

            {{-- ─── Brand selection ─── --}}
            <div class="card mb-3" style="border: 1px solid #bfdbfe; background: #eff6ff;">
                <div class="card-body">
                    <label for="brand_id" class="form-label fw-bold">
                        <i class="bi bi-tag"></i> {{ __('messages.buses.brand') }}
                    </label>
                    <select class="form-select" id="brand_id" name="brand_id">
                        <option value="">{{ __('messages.buses.import_no_brand') }}</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">
                        {{ __('messages.buses.import_brand_hint') }}
                    </small>
                    @if($brands->isEmpty())
                        <div class="alert alert-warning mt-2 mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            {{ __('messages.buses.no_brands_hint') }}
                            — <a href="{{ route('bus-brands.create') }}">{{ __('messages.bus_brands.new') }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">{{ __('messages.buses.import_select_file') }}</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> {{ __('messages.buses.import_button') }}
                </button>
                <a href="{{ route('buses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
