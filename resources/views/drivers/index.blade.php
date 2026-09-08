@extends('layouts.app')

@section('title', 'Drivers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🧑‍✈️ Drivers</h1>
    <div>
        <a href="{{ route('drivers.export') }}" class="btn btn-info">
            <i class="bi bi-download"></i> Export to Excel
        </a>
        <a href="{{ route('drivers.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Import from Excel
        </a>
        <a href="{{ route('drivers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Driver
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $driver)
                    <tr>
                        <td><strong>{{ $driver->code }}</strong></td>
                        <td>{{ $driver->first_name }}</td>
                        <td>{{ $driver->last_name ?? '-' }}</td>
                        <td>{{ $driver->phone ?? '-' }}</td>
                        <td>{{ $driver->position ?? '-' }}</td>
                        <td>
                            <span class="badge-status {{ $driver->is_active ? 'aktiv' : 'passiv' }}">
                                {{ $driver->is_active ? '✅ Active' : '❌ Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('drivers.destroy', $driver) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-person" style="font-size:40px;display:block;margin-bottom:10px;"></i>
                            No drivers yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $drivers->withQueryString()->links() }}
</div>
@endsection