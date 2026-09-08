@extends('layouts.app')

@section('title', 'Add New Status')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ New Daily Status</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('bus-daily-statuses.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="bus_id" class="form-label fw-bold">🚌 Bus <span class="text-danger">*</span></label>
                <select class="form-select" id="bus_id" name="bus_id" required>
                    <option value="">Select Bus...</option>
                    @foreach($buses as $bus)
                        <option value="{{ $bus->id }}">{{ $bus->dqn }} - Route: {{ $bus->route_number ?? '-' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="date" class="form-label fw-bold">📅 Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date" name="date" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="mb-3">
                <label for="status" class="form-label fw-bold">📌 Status <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="status" name="status" required placeholder="e.g.: READY FOR ROUTE">
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Save
                </button>
                <a href="{{ route('bus-daily-statuses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection