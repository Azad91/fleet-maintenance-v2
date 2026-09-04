@extends('layouts.app')

@section('title', 'Yeni Status Əlavə Et')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ Yeni Gündəlik Status</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('bus-daily-statuses.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="bus_id" class="form-label fw-bold">🚌 Avtobus <span class="text-danger">*</span></label>
                <select class="form-select" id="bus_id" name="bus_id" required>
                    <option value="">Avtobus seçin...</option>
                    @foreach($buses as $bus)
                        <option value="{{ $bus->id }}">{{ $bus->dqn }} - Xətt: {{ $bus->route_number ?? '-' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="date" class="form-label fw-bold">📅 Tarix <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date" name="date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="mb-3">
                <label for="status" class="form-label fw-bold">📌 Status <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="status" name="status" required placeholder="Məs: XƏTTƏ ÇIXMAĞA UYĞUN">
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 Qeyd</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Əlavə qeyd..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Yadda Saxla
                </button>
                <a href="{{ route('bus-daily-statuses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
