@extends('layouts.app')

@section('title', __('messages.buses.new_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>🚌 {{ __('messages.buses.new_title') }}</h4>
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

        <form action="{{ route('buses.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="bus_project" class="form-label fw-bold">{{ __('messages.buses.bus_project') }}</label>
                    <input type="text" class="form-control" id="bus_project" name="bus_project"
                           value="{{ old('bus_project') }}"
                           placeholder="{{ __('messages.buses.bus_project_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="vin" class="form-label fw-bold">{{ __('messages.buses.vin') }}</label>
                    <input type="text" class="form-control" id="vin" name="vin"
                           value="{{ old('vin') }}"
                           placeholder="{{ __('messages.buses.vin_placeholder') }}" maxlength="17">
                </div>

                <div class="col-md-6">
                    <label for="uzunluq" class="form-label fw-bold">{{ __('messages.buses.length') }}</label>
                    <input type="number" class="form-control" id="uzunluq" name="uzunluq" step="0.1"
                           value="{{ old('uzunluq') }}"
                           placeholder="{{ __('messages.buses.length_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="route_number" class="form-label fw-bold">{{ __('messages.buses.route_number') }}</label>
                    <input type="text" class="form-control" id="route_number" name="route_number"
                           value="{{ old('route_number') }}"
                           placeholder="{{ __('messages.buses.route_number_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="dqn" class="form-label fw-bold">{{ __('messages.buses.dqn') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="dqn" name="dqn" required
                           value="{{ old('dqn') }}"
                           placeholder="{{ __('messages.buses.dqn_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="engine_number" class="form-label fw-bold">{{ __('messages.buses.engine_number') }}</label>
                    <input type="text" class="form-control" id="engine_number" name="engine_number"
                           value="{{ old('engine_number') }}"
                           placeholder="{{ __('messages.buses.engine_number_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="km" class="form-label fw-bold">{{ __('messages.buses.km') }}</label>
                    <input type="number" class="form-control" id="km" name="km"
                           value="{{ old('km') }}"
                           placeholder="{{ __('messages.buses.km_placeholder') }}" min="0">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('messages.buses.status') }}</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_active" id="is_active_yes" value="1"
                                   {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active_yes">✅ {{ __('messages.buses.status_active') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_active" id="is_active_no" value="0"
                                   {{ old('is_active') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active_no">❌ {{ __('messages.buses.status_inactive') }}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('buses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection