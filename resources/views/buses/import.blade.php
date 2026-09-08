@extends('layouts.app')

@section('title', 'Import Buses from Excel')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 Import Buses from Excel</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('buses.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Excel Format (Full compatible):</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>BUS PROJECT</strong> – Project name (e.g.: 300 ARAÇ PROJESİ)</li>
                    <li><strong>VIN</strong> – Chassis number (17 characters)</li>
                    <li><strong>UZUNLUQ</strong> – Bus length (e.g.: 12 MT.)</li>
                    <li><strong>Xətt №</strong> – Route number</li>
                    <li><strong>DQN</strong> – State registration number <span class="text-danger">*</span></li>
                    <li><strong>MOTOR №</strong> – Engine number</li>
                </ul>
                <p class="mt-2 mb-0 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>#</strong> column is auto-generated, no need to write in Excel.
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
                <a href="{{ route('buses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection