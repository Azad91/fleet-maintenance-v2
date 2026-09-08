@extends('layouts.app')

@section('title', 'New Warehouse Item')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📦 Add New Warehouse Item</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouses.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="code" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="code" name="code" required placeholder="e.g.: D-001">
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="e.g.: Filter">
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label fw-bold">Stock Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="0" placeholder="0">
            </div>

            <div class="mb-3">
                <label for="unit" class="form-label fw-bold">Unit</label>
                <select class="form-select" id="unit" name="unit">
                    <option value="">Select...</option>
                    <option value="ədəd">Piece</option>
                    <option value="litr">Liter</option>
                    <option value="metr">Meter</option>
                    <option value="kq">Kilogram</option>
                    <option value="q">Gram</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-bold">Unit Price (AZN)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="Price per unit">
                <small class="text-muted">Price per 1 piece, 1 liter or 1 meter</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Save
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection