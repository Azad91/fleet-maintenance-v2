@extends('layouts.app')

@section('title', 'Import Drivers from Excel')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 Import Drivers from Excel</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('drivers.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Excel Format:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>kodu</strong> - Driver code <span class="text-danger">*</span></li>
                    <li><strong>ad</strong> - Driver first name <span class="text-danger">*</span></li>
                    <li><strong>soyad</strong> - Driver last name</li>
                    <li><strong>telefon</strong> - Phone number</li>
                    <li><strong>vezifesi</strong> - Position</li>
                    <li><strong>qeyd</strong> - Notes</li>
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
                <a href="{{ route('drivers.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection