@extends('layouts.app')

@section('title', 'Driver Details')

@section('content')
<div class="container">
    <h1>🧑‍✈️ Driver Details</h1>

    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Code</small>
                        <strong>{{ $driver->code }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">First Name</small>
                        <strong>{{ $driver->first_name }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Last Name</small>
                        <strong>{{ $driver->last_name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Phone</small>
                        <strong>{{ $driver->phone ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Position</small>
                        <strong>{{ $driver->position ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Status</small>
                        <strong>
                            <span class="badge-status {{ $driver->is_active ? 'aktiv' : 'passiv' }}">
                                {{ $driver->is_active ? '✅ Active' : '❌ Inactive' }}
                            </span>
                        </strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📝 Notes</small>
                        <strong>{{ $driver->notes ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">⬅ Back</a>
    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-warning">✏️ Edit</a>
</div>
@endsection