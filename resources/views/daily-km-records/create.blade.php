@extends('layouts.app')

@section('title', __('messages.daily_km.new'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ {{ __('messages.daily_km.new') }}</h4>
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

        <form action="{{ route('daily-km-records.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="bus_id" class="form-label fw-bold">🚌 {{ __('messages.daily_km.bus') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="bus_id" name="bus_id" required>
                    <option value="">{{ __('messages.common.select') }}</option>
                    @foreach($buses as $bus)
                        <option value="{{ $bus->id }}">
                            {{ $bus->dqn }} - {{ __('messages.daily_km.route_label', ['route' => $bus->route_number ?? '-']) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="date" class="form-label fw-bold">📅 {{ __('messages.daily_km.date') }} <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date" name="date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="mb-3">
                <label for="km" class="form-label fw-bold">📊 {{ __('messages.daily_km.km') }} <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="km" name="km" required
                       placeholder="{{ __('messages.daily_km.km_placeholder') }}" min="0"
                       value="{{ old('km') }}">
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 {{ __('messages.daily_km.notes') }}</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"
                          placeholder="{{ __('messages.daily_km.notes_placeholder') }}">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection