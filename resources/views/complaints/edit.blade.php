@extends('layouts.app')

@section('title', __('messages.complaints.edit_title'))

@php
    // The DB `time` column returns HH:MM:SS, but <input type="time">
    // only accepts HH:MM. Safely normalize both old() (after a validation
    // error) and the raw DB value down to H:i format.
    $fmtTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('H:i') : '';
@endphp

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ {{ __('messages.complaints.edit_title') }}</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('complaints.update', $complaint->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Bus --}}
            <div class="mb-3">
                <label class="form-label fw-bold">🚌 {{ __('messages.complaints.bus') }}</label>
                <div class="row">
                    <div class="col-md-6">
                        <label>{{ __('messages.buses.dqn') }}</label>
                        <input type="text" class="form-control" id="dqn"
                               value="{{ $complaint->bus->dqn ?? '' }}"
                               oninput="getBusByDqn(this.value)"
                               autocomplete="off"
                               style="text-transform: uppercase;">
                        <div id="dqnHelp" class="form-text"></div>
                    </div>
                    <div class="col-md-6">
                        <label>{{ __('messages.buses.route_number') }}</label>
                        <input type="text" class="form-control" id="route_number"
                               value="{{ $complaint->bus->route_number ?? '' }}"
                               readonly style="background:#e9ecef;">
                    </div>
                </div>
                <input type="hidden" name="bus_id" id="bus_id" value="{{ $complaint->bus_id }}">
            </div>

            {{-- Location (disabled) --}}
            <div class="mb-3">
                <label class="form-label fw-bold">📍 {{ __('messages.complaints.location') }}</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="yer" id="yer_road" value="road"
                            {{ $complaint->yer?->value === 'road' ? 'checked' : '' }} disabled>
                        <label class="form-check-label text-muted" for="yer_road">🛣️ {{ __('enums.location.road') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="yer" id="yer_garage" value="garage"
                            {{ $complaint->yer?->value === 'garage' ? 'checked' : '' }} disabled>
                        <label class="form-check-label text-muted" for="yer_garage">🏠 {{ __('enums.location.garage') }}</label>
                    </div>
                    {{-- Disabled radios are not submitted — send the value via hidden input --}}
                    <input type="hidden" name="yer" value="{{ $complaint->yer?->value }}">
                </div>
            </div>

            {{-- Driver --}}
            <div class="mb-3" id="surucuField">
                <label class="form-label fw-bold">🧑‍✈️ {{ __('messages.complaints.driver') }}</label>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="driver_code" class="form-label">{{ __('messages.complaints.driver_code') }}</label>
                        <input type="text" class="form-control" id="driver_code" name="driver_code"
                               list="driverList" oninput="getDriverByCode(this.value)"
                               value="{{ old('driver_code', $complaint->driver?->code ?? '') }}">
                        <datalist id="driverList">
                            @foreach($drivers ?? [] as $driver)
                                <option value="{{ $driver->code }}">
                            @endforeach
                        </datalist>
                        <div id="driverHelp" class="form-text">{{ __('messages.complaints.driver_help_default') }}</div>
                    </div>
                    <div class="col-md-8">
                        <label for="driver_name" class="form-label">{{ __('messages.complaints.driver_name') }}</label>
                        <input type="text" class="form-control input-disabled" id="driver_name" name="driver_name"
                               readonly value="{{ old('driver_name', $complaint->driver_name ?? '') }}">
                        <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', $complaint->driver_id ?? '') }}">
                    </div>
                </div>
            </div>

            {{-- Service Vehicle (visible only when yer = road) --}}
            @if($complaint->yer?->value === 'road')
                <div class="mb-3" id="serviceVehicleField">
                    <label for="service_vehicle_id" class="form-label fw-bold">
                        🚐 {{ __('messages.complaints.service_vehicle') }}
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="service_vehicle_id" name="service_vehicle_id"
                            onchange="onServiceVehicleChange()" required>
                        <option value="">{{ __('messages.complaints.service_vehicle_placeholder') }}</option>
                        @foreach($serviceVehicles ?? [] as $vehicle)
                            <option value="{{ $vehicle->id }}"
                                {{ old('service_vehicle_id', $complaint->service_vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->name }}
                                @if($vehicle->plate_number) · {{ $vehicle->plate_number }} @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('messages.complaints.service_vehicle_hint') }}</div>
                </div>
            @endif

            {{-- Complaint Type (disabled) --}}
            <div class="mb-3">
                <label class="form-label fw-bold">🏷️ {{ __('messages.complaints.complaint_type') }}</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="complaint_type" value="accident"
                            {{ $complaint->complaint_type?->value === 'accident' ? 'checked' : '' }} disabled>
                        <label class="form-check-label text-muted">🚗 {{ __('enums.complaint_type.accident') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="complaint_type" value="breakdown"
                            {{ $complaint->complaint_type?->value === 'breakdown' ? 'checked' : '' }} disabled>
                        <label class="form-check-label text-muted">⚠️ {{ __('enums.complaint_type.breakdown') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="complaint_type" value="maintenance"
                            {{ $complaint->complaint_type?->value === 'maintenance' ? 'checked' : '' }} disabled>
                        <label class="form-check-label text-muted">🔧 {{ __('enums.complaint_type.maintenance') }}</label>
                    </div>
                    {{-- Disabled radios are not submitted — send the value via hidden input --}}
                    <input type="hidden" name="complaint_type" value="{{ $complaint->complaint_type?->value }}">
                </div>
            </div>

            {{-- Complaints / Service Type --}}
            <div class="mb-3">
                @if($complaint->complaint_type?->value === 'maintenance')
                    {{-- Maintenance: service_km read-only --}}
                    <label class="form-label fw-bold">📝 {{ __('messages.complaints.service_type_label') }}</label>
                    <input type="text" class="form-control" readonly
                           value="{{ $complaint->service_km ? __('messages.complaints.motor_oil_service_label', ['km' => number_format($complaint->service_km, 0, '', '')]) : '—' }}">
                    <input type="hidden" name="service_km" value="{{ $complaint->service_km }}">

                    {{-- Hidden complaints[] — preserve the existing items --}}
                    @foreach($complaint->items as $item)
                        <input type="hidden" name="complaints[]" value="{{ $item->description }}">
                    @endforeach
                @else
                    {{-- Accident / Breakdown: normal complaints dropdown --}}
                    <label class="form-label fw-bold">📝 {{ __('messages.complaints.complaints_list') }}</label>
                    <div id="complaintsContainer">
                        @php
                            $complaintsList = $complaint->items->pluck('description')->toArray();
                        @endphp
                        @if(count($complaintsList) > 0)
                            @foreach($complaintsList as $index => $description)
                                <div class="complaint-item mb-2">
                                    <div class="input-group">
                                        <span class="input-group-text complaint-number">{{ $index + 1 }}.</span>
                                        <select class="form-select" name="complaints[]" required>
                                            <option value="">{{ __('messages.complaints.select_complaint') }}</option>
                                            @foreach($complaintTypes as $type)
                                                <option value="{{ $type->name }}" {{ trim($description) === $type->name ? 'selected' : '' }}>
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-danger" onclick="removeComplaint(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="complaint-item mb-2">
                                <div class="input-group">
                                    <span class="input-group-text complaint-number">1.</span>
                                    <select class="form-select" name="complaints[]" required>
                                        <option value="">{{ __('messages.complaints.select_complaint') }}</option>
                                        @foreach($complaintTypes as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-danger" onclick="removeComplaint(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addComplaint()">
                        <i class="bi bi-plus-circle"></i> {{ __('messages.complaints.add_complaint') }}
                    </button>
                @endif
            </div>

            {{-- KM --}}
            <div class="mb-3">
                <label for="km" class="form-label fw-bold">📊 {{ __('messages.complaints.km') }}</label>
                <input type="number" class="form-control" id="km" name="km"
                       value="{{ old('km', $complaint->km) }}" min="0" readonly style="background:#e9ecef;">
            </div>

            {{-- Reported --}}
            <div id="bildirilmeFields">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">📅 {{ __('messages.complaints.reported_date') }}</label>
                        <input type="date" class="form-control" name="reported_date"
                               value="{{ old('reported_date', $complaint->reported_date ? \Carbon\Carbon::parse($complaint->reported_date)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">🕐 {{ __('messages.complaints.reported_time') }}</label>
                        <input type="time" class="form-control" name="reported_time"
                               value="{{ $fmtTime(old('reported_time', $complaint->reported_time)) }}">
                    </div>
                </div>
            </div>

            {{-- Start / End --}}
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">📅 {{ __('messages.complaints.start_date') }}</label>
                    <input type="date" class="form-control" name="start_date"
                           value="{{ old('start_date', $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">🕐 {{ __('messages.complaints.start_time') }}</label>
                    <input type="time" class="form-control" name="start_time"
                           value="{{ $fmtTime(old('start_time', $complaint->start_time)) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">📅 {{ __('messages.complaints.end_date') }}</label>
                    <input type="date" class="form-control" name="end_date"
                           value="{{ old('end_date', $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">🕐 {{ __('messages.complaints.end_time') }}</label>
                    <input type="time" class="form-control" name="end_time"
                           value="{{ $fmtTime(old('end_time', $complaint->end_time)) }}">
                </div>
            </div>

            {{-- Status --}}
            <div class="mb-3">
                <label for="status" class="form-label fw-bold">📊 {{ __('messages.common.status') }}</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="pending" {{ $complaint->status?->value === 'pending' ? 'selected' : '' }}>
                        ⏳ {{ __('enums.complaint_status.pending') }}
                    </option>
                    <option value="in_progress" {{ $complaint->status?->value === 'in_progress' ? 'selected' : '' }}>
                        🔨 {{ __('enums.complaint_status.in_progress') }}
                    </option>
                </select>
            </div>

            {{-- Parts --}}
            <div class="complaint-details-card p-3 mb-3">
                <h5 class="fw-bold mb-3">🔧 {{ __('messages.complaints.used_parts') }}</h5>
                <div id="detailsContainer">
                    @if(!empty($details) && count($details) > 0)
                        @foreach($details as $index => $detail)
                            <div class="detail-item">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">{{ __('messages.complaints.related_complaint') }}</label>
                                        <select class="form-select" name="details[{{ $index }}][shikayet_index]">
                                            <option value="0">1</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">{{ __('messages.complaints.part_code') }}</label>
                                        <input type="text" class="form-control" name="details[{{ $index }}][code]"
                                               value="{{ $detail['code'] ?? '' }}" oninput="getPartByCode(this)">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">{{ __('messages.complaints.part_name') }}</label>
                                        <input type="text" class="form-control input-disabled" name="details[{{ $index }}][name]"
                                               value="{{ $detail['name'] ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold stock-source-label">{{ __('messages.complaints.stock_qty') }}</label>
                                        <input type="text" class="form-control input-disabled" name="details[{{ $index }}][stock_quantity]"
                                               value="{{ $detail['stock_quantity'] ?? '' }}" readonly>
                                        <div class="form-text part-help"></div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                                        <input type="number" class="form-control" name="details[{{ $index }}][used_quantity]"
                                               value="{{ $detail['used_quantity'] ?? 1 }}" min="0" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">👤 {{ __('messages.complaints.employee') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control"
                                                   name="details[{{ $index }}][employee_code]"
                                                   placeholder="{{ __('messages.employees.code_placeholder') }}"
                                                   value="{{ old("details.$index.employee_code", $detail['employee_code'] ?? '') }}"
                                                   oninput="getEmployeeByCode(this)"
                                                   autocomplete="off"
                                                   style="text-transform: uppercase;">
                                            <input type="text" class="form-control input-disabled" readonly tabindex="-1"
                                                   name="details[{{ $index }}][employee_name]"
                                                   value="{{ old("details.$index.employee_name", $detail['employee_name'] ?? '') }}">
                                            <input type="hidden" name="details[{{ $index }}][employee_id]"
                                                   value="{{ old("details.$index.employee_id", $detail['employee_id'] ?? '') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold">&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">📝 {{ __('messages.complaints.work_done_notes') }}</label>
                                        <textarea class="form-control" name="details[{{ $index }}][notes]" rows="2">{{ $detail['notes'] ?? '' }}</textarea>
                                    </div>
                                </div>
                                <hr>
                            </div>
                        @endforeach
                    @else
                        <div class="detail-item">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">{{ __('messages.complaints.related_complaint') }}</label>
                                    <select class="form-select" name="details[0][shikayet_index]">
                                        <option value="0">1</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">{{ __('messages.complaints.part_code') }}</label>
                                    <input type="text" class="form-control" name="details[0][code]" oninput="getPartByCode(this)">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">{{ __('messages.complaints.part_name') }}</label>
                                    <input type="text" class="form-control input-disabled" name="details[0][name]" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold stock-source-label">{{ __('messages.complaints.stock_qty') }}</label>
                                    <input type="text" class="form-control input-disabled" name="details[0][stock_quantity]" readonly>
                                    <div class="form-text part-help"></div>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                                    <input type="number" class="form-control" name="details[0][used_quantity]" min="0" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">👤 {{ __('messages.complaints.employee') }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control"
                                               name="details[0][employee_code]"
                                               placeholder="{{ __('messages.employees.code_placeholder') }}"
                                               oninput="getEmployeeByCode(this)"
                                               autocomplete="off"
                                               style="text-transform: uppercase;">
                                        <input type="text" class="form-control input-disabled" readonly tabindex="-1"
                                               name="details[0][employee_name]">
                                        <input type="hidden" name="details[0][employee_id]">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <label class="form-label fw-bold">📝 {{ __('messages.complaints.work_done_notes') }}</label>
                                    <textarea class="form-control" name="details[0][notes]" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addDetail()">
                    <i class="bi bi-plus-circle"></i> {{ __('messages.complaints.add_part') }}
                </button>
            </div>

            <div class="d-flex gap-2">
                @can('update', $complaint)
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> {{ __('messages.common.update') }}
                    </button>
                @endcan
                <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ═══════════════════════════════════════════════════════════════
    // BUS
    // ═══════════════════════════════════════════════════════════════
    function getBusByDqn(dqnValue) {
        const input = document.getElementById('dqn');
        const routeInput = document.getElementById('route_number');
        const busIdInput = document.getElementById('bus_id');
        const kmInput = document.getElementById('km');
        const help = document.getElementById('dqnHelp');

        const dqn = (dqnValue || '').trim().toUpperCase();

        routeInput.value = '';
        busIdInput.value = '';
        kmInput.value = '';
        help.textContent = '';
        help.className = 'form-text';
        input.classList.remove('is-valid', 'is-invalid');

        if (!dqn) return;

        fetch('/get-bus-by-dqn/' + encodeURIComponent(dqn), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                routeInput.value = data.route_number || '';
                busIdInput.value = data.bus_id || '';
                kmInput.value = data.km || '';
                input.classList.add('is-valid');
                help.textContent = '';
                help.className = 'form-text';
            } else {
                input.classList.add('is-invalid');
                help.textContent = @json(__('messages.complaints.dqn_not_found'));
                help.className = 'form-text text-danger';
            }
        })
        .catch(err => console.error('Bus search error:', err));
    }

    // ═══════════════════════════════════════════════════════════════
    // DRIVER
    // ═══════════════════════════════════════════════════════════════
    let driverLookupRequest = 0;

    function getDriverByCode(code) {
        const normalizedCode = code.trim().toUpperCase();
        const nameInput = document.getElementById('driver_name');
        const idInput = document.getElementById('driver_id');
        const help = document.getElementById('driverHelp');
        const codeInput = document.getElementById('driver_code');

        idInput.value = '';
        nameInput.value = '';
        codeInput.classList.remove('is-valid', 'is-invalid');

        if (!normalizedCode) {
            help.textContent = @json(__('messages.complaints.driver_help_default'));
            help.className = 'form-text';
            return;
        }

        const requestId = ++driverLookupRequest;
        help.textContent = @json(__('messages.complaints.driver_searching'));

        fetch('/get-driver-by-kod/' + encodeURIComponent(normalizedCode))
            .then(response => response.json())
            .then(data => {
                if (requestId !== driverLookupRequest) return;
                if (data.found) {
                    nameInput.value = data.driver_name;
                    idInput.value = data.driver_id;
                    codeInput.value = normalizedCode;
                    codeInput.classList.add('is-valid');
                    help.textContent = @json(__('messages.complaints.driver_found'));
                    help.className = 'form-text text-success';
                } else {
                    codeInput.classList.add('is-invalid');
                    help.textContent = @json(__('messages.complaints.driver_not_found'));
                    help.className = 'form-text text-danger';
                }
            });
    }

    // ═══════════════════════════════════════════════════════════════
    // EMPLOYEE
    // ═══════════════════════════════════════════════════════════════
    let employeeLookupRequest = 0;

    function getEmployeeByCode(input) {
        const code = input.value.trim().toUpperCase();
        const item = input.closest('.detail-item');
        const nameInput = item.querySelector('input[name*="[employee_name]"]');
        const idInput = item.querySelector('input[name*="[employee_id]"]');

        nameInput.value = '';
        idInput.value = '';
        input.classList.remove('is-valid', 'is-invalid');

        if (!code) return;

        const requestId = ++employeeLookupRequest;

        fetch('/get-employee-by-kod/' + encodeURIComponent(code), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (requestId !== employeeLookupRequest) return;
            if (data.found) {
                nameInput.value = data.employee_name || '';
                idInput.value = data.employee_id || '';
                input.classList.add('is-valid');
            } else {
                input.classList.add('is-invalid');
            }
        })
        .catch(error => console.error('Employee lookup error:', error));
    }

    // ═══════════════════════════════════════════════════════════════
    // PART (context-aware: warehouse vs service vehicle)
    // ═══════════════════════════════════════════════════════════════
    function getPartByCode(input) {
        const code = input.value.trim();
        const item = input.closest('.detail-item');
        const nameInput = item.querySelector('input[name*="[name]"]');
        const stockInput = item.querySelector('input[name*="[stock_quantity]"]');
        const helpEl = item.querySelector('.part-help');

        nameInput.value = '';
        stockInput.value = '';
        input.classList.remove('is-valid', 'is-invalid');
        if (helpEl) {
            helpEl.textContent = '';
            helpEl.className = 'form-text part-help';
        }

        if (!code) return;

        const yer = document.querySelector('input[name="yer"]:checked')?.value;

        // ─── ROAD: source is the selected service vehicle ───
        if (yer === 'road') {
            const vehicleId = document.getElementById('service_vehicle_id')?.value;

            if (!vehicleId) {
                input.classList.add('is-invalid');
                if (helpEl) {
                    helpEl.textContent = @json(__('messages.complaints.select_vehicle_first'));
                    helpEl.className = 'form-text text-danger part-help';
                }
                return;
            }

            fetch('/get-service-vehicle-part-by-code?service_vehicle_id='
                    + encodeURIComponent(vehicleId)
                    + '&code=' + encodeURIComponent(code), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            .then(response => response.json())
            .then(data => {
                if (data.found) {
                    nameInput.value = data.part_name || '';
                    stockInput.value = data.stock_quantity ?? 0;
                    input.classList.add('is-valid');
                    if (helpEl) {
                        helpEl.textContent = @json(__('messages.complaints.service_vehicle')) + ': ' + (data.unit || '');
                        helpEl.className = 'form-text text-success part-help';
                    }
                } else {
                    input.classList.add('is-invalid');
                    if (helpEl) {
                        helpEl.textContent = @json(__('messages.complaints.part_not_on_vehicle'));
                        helpEl.className = 'form-text text-danger part-help';
                    }
                }
            })
            .catch(err => console.error('Service vehicle part lookup error:', err));

            return;
        }

        // ─── GARAGE: source is the warehouse ───
        fetch('/get-detal-by-kod/' + encodeURIComponent(code))
            .then(response => response.json())
            .then(data => {
                nameInput.value = data.detallar_name || '';
                stockInput.value = data.stock_quantity || '';
                if (data.detallar_name) {
                    input.classList.add('is-valid');
                }
            })
            .catch(error => console.error('Part lookup error:', error));
    }

    /**
     * Re-run part lookup for every filled part row when the service
     * vehicle changes — so the stock display reflects the new vehicle.
     */
    function onServiceVehicleChange() {
        document.querySelectorAll('#detailsContainer input[name*="[code]"]').forEach(input => {
            if (input.value.trim()) {
                getPartByCode(input);
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // COMPLAINTS (add / remove)
    // ═══════════════════════════════════════════════════════════════
    function addComplaint() {
        const container = document.getElementById('complaintsContainer');
        if (!container) return;

        const items = container.querySelectorAll('.complaint-item');
        const newNumber = items.length + 1;

        const newItem = document.createElement('div');
        newItem.className = 'complaint-item mb-2';
        newItem.innerHTML = `
            <div class="input-group">
                <span class="input-group-text complaint-number">${newNumber}.</span>
                <select class="form-select" name="complaints[]" required>
                    <option value="">{{ __('messages.complaints.select_complaint') }}</option>
                    @foreach($complaintTypes as $type)
                        <option value="{{ $type->name }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-danger" onclick="removeComplaint(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newItem);
    }

    function removeComplaint(button) {
        const container = document.getElementById('complaintsContainer');
        if (container && container.querySelectorAll('.complaint-item').length > 1) {
            button.closest('.complaint-item').remove();
            container.querySelectorAll('.complaint-number').forEach((el, idx) => {
                el.textContent = (idx + 1) + '.';
            });
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // DETAILS (add / remove)
    // ═══════════════════════════════════════════════════════════════
    let detailCount = {{ !empty($details) ? count($details) : 1 }};

    function addDetail() {
        const container = document.getElementById('detailsContainer');

        const newItem = document.createElement('div');
        newItem.className = 'detail-item';
        newItem.innerHTML = `
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">{{ __('messages.complaints.related_complaint') }}</label>
                    <select class="form-select" name="details[${detailCount}][shikayet_index]">
                        <option value="0">1</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">{{ __('messages.complaints.part_code') }}</label>
                    <input type="text" class="form-control" name="details[${detailCount}][code]" oninput="getPartByCode(this)">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">{{ __('messages.complaints.part_name') }}</label>
                    <input type="text" class="form-control input-disabled" name="details[${detailCount}][name]" readonly>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold stock-source-label">{{ __('messages.complaints.stock_qty') }}</label>
                    <input type="text" class="form-control input-disabled" name="details[${detailCount}][stock_quantity]" readonly>
                    <div class="form-text part-help"></div>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                    <input type="number" class="form-control" name="details[${detailCount}][used_quantity]" min="0" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">👤 {{ __('messages.complaints.employee') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control"
                               name="details[${detailCount}][employee_code]"
                               placeholder="{{ __('messages.employees.code_placeholder') }}"
                               oninput="getEmployeeByCode(this)"
                               autocomplete="off"
                               style="text-transform: uppercase;">
                        <input type="text" class="form-control input-disabled" readonly tabindex="-1"
                               name="details[${detailCount}][employee_name]">
                        <input type="hidden" name="details[${detailCount}][employee_id]">
                    </div>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <label class="form-label fw-bold">📝 {{ __('messages.complaints.work_done_notes') }}</label>
                    <textarea class="form-control" name="details[${detailCount}][notes]" rows="2"></textarea>
                </div>
            </div>
        `;
        container.appendChild(newItem);
        detailCount++;
    }

    function removeDetail(button) {
        const items = document.querySelectorAll('.detail-item');
        if (items.length > 1) {
            button.closest('.detail-item').remove();
        } else {
            const item = button.closest('.detail-item');
            item.querySelectorAll('input').forEach(i => {
                if (i.name && i.name.includes('used_quantity')) {
                    i.value = '0';
                }
            });
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // LOCATION (Road / Garage → driver visibility AND stock label)
    //
    // On edit, `yer` is disabled in the form, so we only need to
    // update the "Stock Qty" label to reflect the correct source
    // (warehouse vs service vehicle).
    // ═══════════════════════════════════════════════════════════════
    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked');
        const driverField = document.getElementById('surucuField');
        const reportFields = document.getElementById('bildirilmeFields');
        const vehicleField = document.getElementById('serviceVehicleField');

        if (!yer) {
            if (driverField) driverField.style.display = 'none';
            if (reportFields) reportFields.style.display = 'none';
            if (vehicleField) vehicleField.style.display = 'none';
            return;
        }

        if (yer.value === 'garage') {
            if (driverField) driverField.style.display = 'none';
            if (reportFields) reportFields.style.display = 'none';
            if (vehicleField) vehicleField.style.display = 'none';
        } else {
            if (driverField) driverField.style.display = 'block';
            if (reportFields) reportFields.style.display = 'block';
            if (vehicleField) vehicleField.style.display = 'block';
        }

        // ─── Update the stock label so the operator knows the source ───
        const label = yer.value === 'road'
            ? @json(__('messages.complaints.service_vehicle'))
            : @json(__('messages.warehouse.quantity'));

        document.querySelectorAll('.stock-source-label').forEach(el => {
            el.textContent = label;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();

        // Re-lookup prefilled parts so the stock display reflects the
        // correct source (warehouse vs vehicle) on edit page load.
        document.querySelectorAll('#detailsContainer input[name*="[code]"]').forEach(input => {
            if (input.value.trim()) {
                getPartByCode(input);
            }
        });
    });
</script>
@endsection
