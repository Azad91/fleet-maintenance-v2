<div class="mb-3">
    <label for="code" class="form-label fw-bold">Kod <span class="text-danger">*</span></label> <!-- ✅ kodu → code -->
    <input type="text" class="form-control" id="code" name="code" required
           value="{{ old('code', $driver->code ?? '') }}" placeholder="Məs: D-001">
</div>

<div class="mb-3">
    <label for="first_name" class="form-label fw-bold">Ad <span class="text-danger">*</span></label> <!-- ✅ ad → first_name -->
    <input type="text" class="form-control" id="first_name" name="first_name" required
           value="{{ old('first_name', $driver->first_name ?? '') }}" placeholder="Məs: Elşad">
</div>

<div class="mb-3">
    <label for="last_name" class="form-label fw-bold">Soyad</label> <!-- ✅ soyad → last_name -->
    <input type="text" class="form-control" id="last_name" name="last_name"
           value="{{ old('last_name', $driver->last_name ?? '') }}" placeholder="Məs: Məmmədov">
</div>

<div class="mb-3">
    <label for="phone" class="form-label fw-bold">Telefon</label> <!-- ✅ telefon → phone -->
    <input type="text" class="form-control" id="phone" name="phone"
           value="{{ old('phone', $driver->phone ?? '') }}" placeholder="Məs: +994 50 123 45 67">
</div>

<div class="mb-3">
    <label for="position" class="form-label fw-bold">Vəzifəsi</label> <!-- ✅ vezifesi → position -->
    <input type="text" class="form-control" id="position" name="position"
           value="{{ old('position', $driver->position ?? '') }}" placeholder="Məs: Əsas Sürücü">
</div>

<div class="mb-3">
    <label class="form-label fw-bold">Status</label>
    <div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="is_active" id="is_active_yes" value="1" <!-- ✅ aktiv → is_active -->
                   {{ old('is_active', $driver->is_active ?? true) == '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active_yes">✅ Aktiv</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="is_active" id="is_active_no" value="0"
                   {{ old('is_active', $driver->is_active ?? true) == '0' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active_no">❌ Passiv</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label fw-bold">📝 Qeyd</label> <!-- ✅ qeyd → notes -->
    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $driver->notes ?? '') }}</textarea>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-save"></i> Yadda Saxla
    </button>
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Geri
    </a>
</div>
