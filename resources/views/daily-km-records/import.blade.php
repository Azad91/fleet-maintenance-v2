@extends('layouts.app')

@section('title', 'Import KM from Excel')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 Import Daily KM from Excel</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('daily-km-records.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Excel Format:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>PLAKA NO (DQN)</strong> – Bus DQN <span class="text-danger">*</span></li>
                    <li><strong>KM</strong> – Mileage (number)</li>
                    <li><strong>Date</strong> – Date written in the column header (auto-read)</li>
                </ul>
                <p class="mt-2 mb-0 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Other columns (ROUTE, FUEL, etc.) are ignored.
                </p>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">Select Excel File (.xlsx, .xls, .csv)</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> Import
                </button>
                <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection