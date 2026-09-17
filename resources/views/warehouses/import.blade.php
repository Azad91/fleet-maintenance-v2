@extends('layouts.app')

@section('title', __('messages.warehouse.import_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.warehouse.import_title') }}</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouses.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>{{ __('messages.complaints.import_format_title') }}:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>code</strong> — {{ __('messages.warehouse.code') }} <span class="text-danger">*</span></li>
                    <li><strong>name</strong> — {{ __('messages.warehouse.name') }} <span class="text-danger">*</span></li>
                    <li><strong>quantity</strong> — {{ __('messages.warehouse.quantity') }}</li>
                    <li><strong>unit</strong> — {{ __('messages.warehouse.unit') }}</li>
                    <li><strong>price</strong> — {{ __('messages.warehouse.price') }}</li>
                </ul>
            </div>

            {{-- ─── Import mode ─── --}}
            <div class="card mb-3" style="border: 1px solid #e5eaf1;">
                <div class="card-body">
                    <label class="form-label fw-bold">
                        <i class="bi bi-sliders"></i> {{ __('messages.warehouse.import_mode_label') }}
                    </label>
                    <p class="text-muted small mb-3">
                        {{ __('messages.warehouse.import_mode_hint') }}
                    </p>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="mode" id="mode_overwrite"
                               value="overwrite"
                               {{ old('mode', 'overwrite') === 'overwrite' ? 'checked' : '' }}>
                        <label class="form-check-label" for="mode_overwrite">
                            <strong>{{ __('messages.warehouse.import_mode_overwrite') }}</strong>
                            <br>
                            <small class="text-muted">
                                {{ __('messages.warehouse.import_mode_overwrite_hint') }}
                            </small>
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode_add"
                               value="add"
                               {{ old('mode') === 'add' ? 'checked' : '' }}>
                        <label class="form-check-label" for="mode_add">
                            <strong>{{ __('messages.warehouse.import_mode_add') }}</strong>
                            <br>
                            <small class="text-muted">
                                {{ __('messages.warehouse.import_mode_add_hint') }}
                            </small>
                        </label>
                    </div>
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
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
