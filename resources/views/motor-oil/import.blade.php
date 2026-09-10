@extends('layouts.app')

@section('title', __('messages.motor_oil.import_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.motor_oil.import_title') }}</h4>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
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

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">{{ __('messages.buses.import_select_file') }}</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-upload"></i> {{ __('messages.buses.import_button') }}
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
        </form>
    </div>
</div>
@endsection