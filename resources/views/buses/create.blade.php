@extends('layouts.app')

@section('title', 'Yeni Avtobus')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>🚌 Yeni Avtobus Əlavə Et</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('buses.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <!-- BUS PROJECT -->
                <div class="col-md-6">
                    <label for="bus_project" class="form-label fw-bold">BUS PROJECT</label>
                    <input type="text" class="form-control" id="bus_project" name="bus_project" value="{{ old('bus_project') }}" placeholder="Məs: BakuBus">
                </div>

                <!-- VIN -->
                <div class="col-md-6">
                    <label for="vin" class="form-label fw-bold">VIN (Şassi №)</label>
                    <input type="text" class="form-control" id="vin" name="vin" value="{{ old('vin') }}" placeholder="17 simvol" maxlength="17">
                </div>

                <!-- UZUNLUQ -->
                <div class="col-md-6">
                    <label for="uzunluq" class="form-label fw-bold">UZUNLUQ (metr)</label>
                    <input type="number" class="form-control" id="uzunluq" name="uzunluq" step="0.1" value="{{ old('uzunluq') }}" placeholder="Məs: 12.5">
                </div>

                <!-- Xətt № - ✅ name dəyişdi -->
                <div class="col-md-6">
                    <label for="route_number" class="form-label fw-bold">Xətt №</label>
                    <input type="text" class="form-control" id="route_number" name="route_number" value="{{ old('route_number') }}" placeholder="Məs: 15405">
                </div>

                <!-- DQN -->
                <div class="col-md-6">
                    <label for="dqn" class="form-label fw-bold">DQN <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="dqn" name="dqn" required value="{{ old('dqn') }}" placeholder="Məs: 90-AA-123">
                </div>

                <!-- MOTOR № - ✅ name dəyişdi -->
                <div class="col-md-6">
                    <label for="engine_number" class="form-label fw-bold">MOTOR №</label>
                    <input type="text" class="form-control" id="engine_number" name="engine_number" value="{{ old('engine_number') }}" placeholder="Məs: M-123456">
                </div>

                <!-- KM -->
                <div class="col-md-6">
                    <label for="km" class="form-label fw-bold">KM (Yürüş)</label>
                    <input type="number" class="form-control" id="km" name="km" value="{{ old('km') }}" placeholder="0" min="0">
                </div>

                <!-- Aktiv / Passiv - ✅ name dəyişdi -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Status</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_active" id="is_active_yes" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active_yes">✅ Aktiv</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_active" id="is_active_no" value="0" {{ old('is_active') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active_no">❌ Passiv</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Yadda Saxla
                </button>
                <a href="{{ route('buses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
