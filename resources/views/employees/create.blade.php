@extends('layouts.app')

@section('title', 'New Employee')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ Add New Employee</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="first_name" class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" required placeholder="e.g.: Elshad" value="{{ old('first_name') }}">
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" required placeholder="e.g.: Mammadov" value="{{ old('last_name') }}">
            </div>

            <div class="mb-3">
                <label for="position" class="form-label fw-bold">Position <span class="text-danger">*</span></label>
                <select class="form-select" id="position" name="position" required>
                    <option value="">Select position...</option>
                    @foreach($positions as $key => $label)
                        <option value="{{ $key }}" {{ old('position') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">✅ Active</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Save
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection