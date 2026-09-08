@extends('layouts.app')

@section('title', 'Import Warehouse from Excel')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 Import Warehouse from Excel</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouses.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Excel Format:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>kod</strong> - Product code <span class="text-danger">*</span> (required, unique)</li>
                    <li><strong>ad</strong> - Product name <span class="text-danger">*</span></li>
                    <li><strong>miqdar</strong> - Stock quantity</li>
                    <li><strong>olcu_vahidi</strong> - Unit (piece, liter, meter, kg, g)</li>
                    <li><strong>qiymet</strong> - Price (AZN)</li>
                </ul>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">Select Excel File (.xlsx, .xls, .csv)</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> Import
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection