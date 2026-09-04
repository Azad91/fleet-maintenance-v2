@extends('layouts.app')

@section('title', 'Yeni KM Qeydi')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ Yeni KM Qeydi Əlavə Et</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('daily-km-records.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="bus_id" class="form-label fw-bold">🚌 Avtobus <span class="text-danger">*</span></label>
                <select class="form-select" id="bus_id" name="bus_id" required>
                    <option value="">Avtobus seçin...</option>
                    @foreach($buses as $bus)
                        <option value="{{ $bus->id }}">{{ $bus->dqn }} - Xətt: {{ $bus->route_number ?? '-' }}</option> <!-- ✅ xett_no → route_number -->
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="date" class="form-label fw-bold">📅 Tarix <span class="text-danger">*</span></label> <!-- ✅ tarix → date -->
                <input type="date" class="form-control" id="date" name="date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="mb-3">
                <label for="km" class="form-label fw-bold">📊 KM (Yürüş) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="km" name="km" required placeholder="Məs: 36000" min="0">
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 Qeyd</label> <!-- ✅ qeyd → notes -->
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Əlavə qeyd..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Yadda Saxla
                </button>
                <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
