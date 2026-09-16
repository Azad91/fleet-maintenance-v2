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
        <label for="name" class="form-label fw-bold">
            {{ __('messages.service_vehicles.name') }} <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="name" name="name" required autofocus
               value="{{ old('name', $vehicle->name ?? '') }}"
               placeholder="{{ __('messages.service_vehicles.name_placeholder') }}">
    </div>

    <div class="col-md-6">
        <label for="plate_number" class="form-label fw-bold">
            {{ __('messages.service_vehicles.plate_number') }}
        </label>
        <input type="text" class="form-control" id="plate_number" name="plate_number"
               value="{{ old('plate_number', $vehicle->plate_number ?? '') }}"
               placeholder="{{ __('messages.service_vehicles.plate_number_placeholder') }}"
               style="text-transform: uppercase;">
        <small class="text-muted">{{ __('messages.service_vehicles.plate_number_hint') }}</small>
    </div>

    <div class="col-md-6">
        <label for="driver_name" class="form-label fw-bold">
            {{ __('messages.service_vehicles.driver_name') }}
        </label>
        <input type="text" class="form-control" id="driver_name" name="driver_name"
               value="{{ old('driver_name', $vehicle->driver_name ?? '') }}"
               placeholder="{{ __('messages.service_vehicles.driver_name_placeholder') }}">
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label fw-bold">
            {{ __('messages.service_vehicles.phone') }}
        </label>
        <input type="text" class="form-control" id="phone" name="phone"
               value="{{ old('phone', $vehicle->phone ?? '') }}"
               placeholder="{{ __('messages.service_vehicles.phone_placeholder') }}">
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                   @checked(old('is_active', $vehicle->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                {{ __('messages.common.active') }}
            </label>
        </div>
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-bold">
            {{ __('messages.common.notes') }}
        </label>
        <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes', $vehicle->notes ?? '') }}</textarea>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> {{ $submitLabel }}
    </button>
    <a href="{{ route('service-vehicles.index') }}" class="btn btn-secondary">
        {{ __('messages.common.cancel') }}
    </a>
</div>
