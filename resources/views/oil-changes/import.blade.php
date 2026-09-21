@extends('layouts.app')

@section('title', __('messages.oil_change.import_title'))

@php
    use App\Enums\OilType;
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-0">📂 {{ __('messages.oil_change.import_title') }}</h1>
    </div>
    <a href="{{ route('oil-changes.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

@if(session('error'))
    <div class="fleet-alert fleet-alert--error">
        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('oil-changes.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="type" class="form-label fw-bold">
                    {{ __('messages.oil_change.type') }} <span class="text-danger">*</span>
                </label>
                <select name="type" id="type" class="form-select" required onchange="onTypeChange()">
                    @foreach(OilType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('type', 'motor') === $t->value)>
                            {{ $t->icon() }} {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3" id="busLengthField">
                <label for="bus_length" class="form-label fw-bold">
                    {{ __('messages.oil_change.bus_length') }}
                </label>
                <select name="bus_length" id="bus_length" class="form-select">
                    <option value="12" @selected(old('bus_length', 12) == 12)>12m ({{ number_format(36000, 0, '', '.') }} km)</option>
                    <option value="18" @selected(old('bus_length') == 18)>18m ({{ number_format(30000, 0, '', '.') }} km)</option>
                </select>
                <small class="text-muted">
                    {{ __('messages.oil_change.bus_length_hint') }}
                </small>
            </div>

            <div class="alert alert-info">
                <strong>📋 {{ __('messages.imports.excel_format') }}:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>DQN</strong> {{ __('messages.common.or') }} <strong>PLAKA No</strong> — {{ __('messages.oil_change.bus') }} (məcburi)</li>
                    <li id="hintMotor">Başlıqlar <code>324000 BAKIMI</code>, <code>360000 BAKIMI</code> şəklində</li>
                    <li id="hintGearbox" style="display:none">Başlıqlar <code>SHELL 1</code>, <code>LUK 1</code> şəklində (brend başlıqdan götürülür)</li>
                    <li id="hintAxle" style="display:none">Başlıqlar <code>360</code>, <code>540</code>, <code>720</code>, <code>900</code> (min km)</li>
                </ul>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">
                    {{ __('messages.imports.select_file') }}
                </label>
                <input type="file" name="file" id="file" class="form-control"
                       accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> {{ __('messages.imports.import_button') }}
                </button>
                <a href="{{ route('oil-changes.index') }}" class="btn btn-secondary">
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function onTypeChange() {
        const type = document.getElementById('type').value;
        const busLength = document.getElementById('busLengthField');

        busLength.style.display = (type === 'motor') ? 'block' : 'none';

        document.getElementById('hintMotor').style.display   = (type === 'motor')   ? 'list-item' : 'none';
        document.getElementById('hintGearbox').style.display = (type === 'gearbox') ? 'list-item' : 'none';
        document.getElementById('hintAxle').style.display    = (type === 'axle')    ? 'list-item' : 'none';
    }

    document.addEventListener('DOMContentLoaded', onTypeChange);
</script>
@endpush
