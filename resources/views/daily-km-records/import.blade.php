@extends('layouts.app')

@section('title', __('messages.daily_km.import'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.daily_km.import') }}</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('daily-km-records.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>{{ __('messages.complaints.import_format_title') }}:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>DQN</strong> – {{ __('messages.daily_km.bus') }} <span class="text-danger">*</span></li>
                    <li><strong>KM</strong> – {{ __('messages.daily_km.km') }}</li>
                    <li><strong>Date</strong> – {{ __('messages.daily_km.date') }} ({{ __('messages.daily_km.auto_read') }})</li>
                </ul>
                <p class="mt-2 mb-0 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    {{ __('messages.daily_km.ignored_columns') }}
                </p>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">{{ __('messages.buses.import_select_file') }}</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> {{ __('messages.buses.import_button') }}
                </button>
                <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection