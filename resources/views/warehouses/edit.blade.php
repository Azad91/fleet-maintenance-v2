@extends('layouts.app')

@section('title', 'Anbar Məhsulu - Redaktə Et')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ Anbar Məhsulu - Redaktə Et</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="code" class="form-label fw-bold">Kod <span class="text-danger">*</span></label> <!-- ✅ kod → code -->
                <input type="text" class="form-control" id="code" name="code" required value="{{ old('code', $warehouse->code) }}">
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Ad <span class="text-danger">*</span></label> <!-- ✅ ad → name -->
                <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $warehouse->name) }}">
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label fw-bold">Depo Miqdarı</label> <!-- ✅ miqdar → quantity -->
                <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="{{ old('quantity', $warehouse->quantity) }}">
            </div>

            <div class="mb-3">
                <label for="unit" class="form-label fw-bold">Ölçü Vahidi</label> <!-- ✅ olcu_vahidi → unit -->
                <select class="form-select" id="unit" name="unit">
                    <option value="">Seç...</option>
                    <option value="ədəd" {{ $warehouse->unit == 'ədəd' ? 'selected' : '' }}>Ədəd</option>
                    <option value="litr" {{ $warehouse->unit == 'litr' ? 'selected' : '' }}>Litr</option>
                    <option value="metr" {{ $warehouse->unit == 'metr' ? 'selected' : '' }}>Metr</option>
                    <option value="kq" {{ $warehouse->unit == 'kq' ? 'selected' : '' }}>Kiloqram</option>
                    <option value="q" {{ $warehouse->unit == 'q' ? 'selected' : '' }}>Qram</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-bold">Qiymət (AZN)</label> <!-- ✅ qiymet → price -->
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="{{ old('price', $warehouse->price) }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Yenilə
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
