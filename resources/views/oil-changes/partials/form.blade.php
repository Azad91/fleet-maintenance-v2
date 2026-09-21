@php
    use App\Enums\OilType;

    $isEdit = isset($change) && $change->exists;
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ __('messages.users.not_saved') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label for="bus_id" class="form-label fw-bold">
            {{ __('messages.oil_change.bus') }} <span class="text-danger">*</span>
        </label>
        <select name="bus_id" id="bus_id" class="form-select" required>
            <option value="">{{ __('messages.common.select') }}</option>
            @foreach($buses as $bus)
                <option value="{{ $bus->id }}"
                    @selected(old('bus_id', $change->bus_id ?? $selectedBusId ?? null) == $bus->id)>
                    {{ $bus->dqn }}
                    @if($bus->route_number) · {{ __('messages.daily_km.route_label', ['route' => $bus->route_number]) }} @endif
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="oil_type" class="form-label fw-bold">
            {{ __('messages.oil_change.type') }} <span class="text-danger">*</span>
        </label>
        <select name="oil_type" id="oil_type" class="form-select" required
                onchange="onTypeChange()">
            @foreach(OilType::cases() as $type)
                <option value="{{ $type->value }}"
                    @selected(old('oil_type', $change->oil_type?->value ?? $selectedOilType ?? 'motor') === $type->value)>
                    {{ $type->icon() }} {{ $type->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6" id="brandField">
        <label for="oil_brand" class="form-label fw-bold">
            {{ __('messages.oil_change.brand') }} <span class="text-danger">*</span>
        </label>
        <input type="text" name="oil_brand" id="oil_brand" class="form-control"
               value="{{ old('oil_brand', $change->oil_brand ?? '') }}"
               placeholder="SHELL, LUK, ..."
               style="text-transform: uppercase;">
        <small class="text-muted">
            @foreach(config('oil.intervals.gearbox', []) as $brand => $km)
                <span class="badge bg-secondary me-1">
                    {{ $brand }}: {{ number_format($km, 0, '', '.') }} km
                </span>
            @endforeach
        </small>
    </div>

    <div class="col-md-6">
        <label for="scheduled_km" class="form-label fw-bold">
            {{ __('messages.oil_change.scheduled_km') }}
        </label>
        <input type="number" name="scheduled_km" id="scheduled_km" class="form-control"
               min="0"
               value="{{ old('scheduled_km', $change->scheduled_km ?? '') }}"
               placeholder="180000">
    </div>

    <div class="col-md-6">
        <label for="actual_km" class="form-label fw-bold">
            {{ __('messages.oil_change.actual_km') }} <span class="text-danger">*</span>
        </label>
        <input type="number" name="actual_km" id="actual_km" class="form-control"
               min="0" required
               value="{{ old('actual_km', $change->actual_km ?? '') }}">
    </div>

    <div class="col-md-6">
        <label for="changed_at" class="form-label fw-bold">
            {{ __('messages.oil_change.changed_at') }}
        </label>
        <input type="date" name="changed_at" id="changed_at" class="form-control"
               value="{{ old('changed_at', $change->changed_at?->format('Y-m-d') ?? '') }}">
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-bold">
            {{ __('messages.oil_change.notes') }}
        </label>
        <textarea name="notes" id="notes" class="form-control" rows="2"
                  maxlength="2000">{{ old('notes', $change->notes ?? '') }}</textarea>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> {{ $submitLabel }}
    </button>
    <a href="{{ $isEdit ? route('oil-changes.show', $change->bus_id) : route('oil-changes.index') }}"
       class="btn btn-secondary">
        {{ __('messages.common.cancel') }}
    </a>
</div>

@push('scripts')
<script>
    function onTypeChange() {
        const type = document.getElementById('oil_type')?.value;
        const brandField = document.getElementById('brandField');
        const brandInput = document.getElementById('oil_brand');

        if (!brandField || !brandInput) return;

        if (type === 'gearbox') {
            brandField.style.display = 'block';
            brandInput.required = true;
        } else {
            brandField.style.display = 'none';
            brandInput.required = false;
            brandInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', onTypeChange);
</script>
@endpush
