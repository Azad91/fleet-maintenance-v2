@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ Edit Employee</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.update', $employee) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="first_name" class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" required value="{{ old('first_name', $employee->first_name) }}">
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" required value="{{ old('last_name', $employee->last_name) }}">
            </div>

            <div class="mb-3">
                <label for="position" class="form-label fw-bold">Position <span class="text-danger">*</span></label>
                <select class="form-select" id="position" name="position" required>
                    <option value="">Select position...</option>
                    @foreach($positions as $key => $label)
                        <option value="{{ $key }}" {{ old('position', $employee->position) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes...">{{ old('notes', $employee->notes) }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $employee->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">✅ Active</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i] Update
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection