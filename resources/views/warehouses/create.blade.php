@extends('layouts.app')

@section('title', 'Yeni Anbar Məhsulu')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📦 Yeni Anbar Məhsulu Əlavə Et</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouses.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="code" class="form-label fw-bold">Kod <span class="text-danger">*</span></label> <!-- ✅ kod → code -->
                <input type="text" class="form-control" id="code" name="code" required placeholder="Məs: D-001">
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Ad <span class="text-danger">*</span></label> <!-- ✅ ad → name -->
                <input type="text" class="form-control" id="name" name="name" required placeholder="Məs: Filtr">
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label fw-bold">Depo Miqdarı (Anbarda olan qalıq)</label> <!-- ✅ miqdar → quantity -->
                <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="0" placeholder="0">
            </div>

            <div class="mb-3">
                <label for="unit" class="form-label fw-bold">Ölçü Vahidi</label> <!-- ✅ olcu_vahidi → unit -->
                <select class="form-select" id="unit" name="unit">
                    <option value="">Seç...</option>
                    <option value="ədəd">Ədəd</option>
                    <option value="litr">Litr</option>
                    <option value="metr">Metr</option>
                    <option value="kq">Kiloqram</option>
                    <option value="q">Qram</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-bold">Vahid Qiyməti (AZN)</label> <!-- ✅ qiymet → price -->
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="1 ədəd/litr/metr üçün qiymət">
                <small class="text-muted">1 ədəd, 1 litr və ya 1 metr üçün qiymət</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Yadda Saxla
                </button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
