@extends('layouts.app')

@section('title', 'Motor Oil Details - Import from Excel')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 Motor Oil Details - Import from Excel</h4>
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
                <strong>Excel Format:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>adi</strong> - Part name</li>
                    <li><strong>olcu_vahidi</strong> - Unit (liter, piece, kg)</li>
                    <li><strong>miqdar</strong> - Quantity per change</li>
                    <li><strong>kod</strong> - Part code</li>
                    <li><strong>36000, 72000, ...</strong> - KM columns (how many times to change)</li>
                </ul>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">Select Excel File (.xlsx, .xls, .csv)</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-upload"></i> Import
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </form>
    </div>
</div>
@endsection