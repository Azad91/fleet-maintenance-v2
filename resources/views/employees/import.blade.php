@extends('layouts.app')

@section('title', __('messages.employees.import_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.employees.import_title') }}</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>{{ __('messages.complaints.import_format_title') }}:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>first_name</strong> — {{ __('messages.employees.first_name') }} <span class="text-danger">*</span></li>
                    <li><strong>last_name</strong> — {{ __('messages.employees.last_name') }} <span class="text-danger">*</span></li>
                    <li><strong>position</strong> — {{ __('messages.employees.position') }}</li>
                    <li><strong>is_active</strong> — {{ __('messages.common.active') }} (true / false)</li>
                    <li><strong>notes</strong> — {{ __('messages.common.notes') }}</li>
                </ul>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">{{ __('messages.buses.import_select_file') }}</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> {{ __('messages.buses.import_button') }}
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection