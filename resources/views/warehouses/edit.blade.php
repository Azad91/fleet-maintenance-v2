@extends('layouts.app')

@section('title', 'Edit Warehouse Item')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ Edit Warehouse Item</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="code" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="code" name="code" required value="{{ old('code', $warehouse->code) }}">
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $warehouse->name) }}">
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label fw-bold">Stock Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="{{ old('quantity', $warehouse->quantity) }}">
            </div>

            <div class="mb-3">
                <label for="unit" class="form-label fw-bold">Unit</label>
                <select class="form-select" id="unit" name="unit">
                    <option value="">Select...</option>
                    <option value="ədəd" {{ $warehouse->unit == 'ədəd' ? 'selected' : '' }}>Piece</option>
                    <option value="litr" {{ $warehouse->unit == 'litr' ? 'selected' : '' }}>Liter</option>
                    <option value="metr" {{ $warehouse->unit == 'metr' ? 'selected' : '' }}>Meter</option>
                    <option value="kq" {{ $warehouse->unit == 'kq' ? 'selected' : '' }}>Kilogram</option>
                    <option value="q" {{ $warehouse->unit == 'q' ? 'selected' : '' }}>Gram</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-bold">Unit Price (AZN)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="{{ old('price', $warehouse->price) }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Update
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection