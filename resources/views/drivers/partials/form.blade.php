<div class="mb-3">
    <label for="code" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="code" name="code" required
           value="{{ old('code', $driver->code ?? '') }}" placeholder="e.g.: D-001">
</div>

<div class="mb-3">
    <label for="first_name" class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="first_name" name="first_name" required
           value="{{ old('first_name', $driver->first_name ?? '') }}" placeholder="e.g.: Elshad">
</div>

<div class="mb-3">
    <label for="last_name" class="form-label fw-bold">Last Name</label>
    <input type="text" class="form-control" id="last_name" name="last_name"
           value="{{ old('last_name', $driver->last_name ?? '') }}" placeholder="e.g.: Mammadov">
</div>

<div class="mb-3">
    <label for="phone" class="form-label fw-bold">Phone</label>
    <input type="text" class="form-control" id="phone" name="phone"
           value="{{ old('phone', $driver->phone ?? '') }}" placeholder="e.g.: +994 50 123 45 67">
</div>

<div class="mb-3">
    <label for="position" class="form-label fw-bold">Position</label>
    <input type="text" class="form-control" id="position" name="position"
           value="{{ old('position', $driver->position ?? '') }}" placeholder="e.g.: Main Driver">
</div>

<div class="mb-3">
    <label class="form-label fw-bold">Status</label>
    <div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="is_active" id="is_active_yes" value="1"
                   {{ old('is_active', $driver->is_active ?? true) == '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active_yes">✅ Active</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="is_active" id="is_active_no" value="0"
                   {{ old('is_active', $driver->is_active ?? true) == '0' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active_no">❌ Inactive</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label fw-bold">📝 Notes</label>
    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $driver->notes ?? '') }}</textarea>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-save"></i> Save
    </button>
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>