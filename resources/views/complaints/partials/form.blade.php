@php
    // ─── Form context ───
    // `new Complaint` (create) → exists = false
    // `findOrFail` (edit)   → exists = true
    $isCreate = ! ($complaint->exists ?? false);

    // ─── Defaults for create ───
    $defaultStartDate = $isCreate ? now()->format('Y-m-d') : '';
    $defaultStartTime = $isCreate ? now()->format('H:i') : '';

    // ─── Complaints list (old input fallback) ───
    $defaultComplaints = isset($complaint) && $complaint->items
        ? $complaint->items->pluck('description')->toArray()
        : [];
    $complaintsList = old('complaints', $defaultComplaints);
    if (! is_array($complaintsList)) {
        $complaintsList = [];
    }

    // ─── Details (old input fallback) ───
    $detailsData = old('details', $details ?? []);
    if (! is_array($detailsData)) {
        $detailsData = [];
    }

    $detailCount = 1;
    if (! empty($detailsData)) {
        $numericKeys = array_filter(array_keys($detailsData), 'is_numeric');
        if (! empty($numericKeys)) {
            $detailCount = (int) max($numericKeys) + 1;
        }
    }
@endphp

<div class="row">
    {{-- Complaint Type --}}
    <div class="col-md-12 mb-3">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-bold">🏷️ {{ __('messages.complaints.complaint_type') }}</label>
                <div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="accident"
                            {{ old('complaint_type', $complaint->complaint_type?->value) === 'accident' ? 'checked' : '' }}
                            onchange="handleComplaintTypeChange()">
                        <label class="form-check-label">🚗 {{ __('enums.complaint_type.accident') }}</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="breakdown"
                            {{ old('complaint_type', $complaint->complaint_type?->value) === 'breakdown' ? 'checked' : '' }}
                            onchange="handleComplaintTypeChange()">
                        <label class="form-check-label">⚠️ {{ __('enums.complaint_type.breakdown') }}</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="maintenance"
                            {{ old('complaint_type', $complaint->complaint_type?->value) === 'maintenance' ? 'checked' : '' }}
                            onchange="handleComplaintTypeChange()">
                        <label class="form-check-label">🔧 {{ __('enums.complaint_type.maintenance') }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bus --}}
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">🚌 {{ __('messages.complaints.bus') }}</label>
        <div class="row">
            <div class="col-md-6">
                <label>{{ __('messages.buses.dqn') }}</label>
                <input type="text" class="form-control" id="dqn"
                       placeholder="{{ __('messages.buses.dqn_placeholder') }}"
                       value="{{ old('dqn', $complaint->bus?->dqn ?? '') }}"
                       oninput="getBusByDqn(this.value)"
                       autocomplete="off"
                       style="text-transform: uppercase;">
                <div id="dqnHelp" class="form-text"></div>
            </div>
            <div class="col-md-6">
                <label>{{ __('messages.buses.route_number') }}</label>
                <input type="text" class="form-control" id="route_number"
                       value="{{ old('route_number', $complaint->bus?->route_number ?? '') }}"
                       readonly style="background:#e9ecef;">
            </div>
        </div>
        <input type="hidden" name="bus_id" id="bus_id" value="{{ old('bus_id', $complaint->bus_id ?? '') }}">
    </div>

    {{-- Location --}}
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">📍 {{ __('messages.complaints.location') }}</label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="yer" id="yer_road" value="road"
                    {{ old('yer', $complaint->yer?->value) === 'road' ? 'checked' : '' }} onchange="toggleFields()">
                <label class="form-check-label" for="yer_road">🛣️ {{ __('enums.location.road') }}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="yer" id="yer_garage" value="garage"
                    {{ old('yer', $complaint->yer?->value) === 'garage' ? 'checked' : '' }} onchange="toggleFields()">
                <label class="form-check-label" for="yer_garage">🏠 {{ __('enums.location.garage') }}</label>
            </div>
        </div>
    </div>

    {{-- Driver --}}
    <div class="col-md-12 mb-3" id="surucuField">
        <label class="form-label fw-bold">🧑‍✈️ {{ __('messages.complaints.driver') }}</label>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="driver_code" class="form-label">{{ __('messages.complaints.driver_code') }}</label>
                <input type="text" class="form-control" id="driver_code" name="driver_code"
                       placeholder="{{ __('messages.complaints.driver_placeholder') }}"
                       list="driverList"
                       oninput="getDriverByCode(this.value)"
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
                       readonly
                       value="{{ old('driver_name', $complaint->driver_name ?? '') }}">
                <input type="hidden" name="driver_id" id="driver_id"
                       value="{{ old('driver_id', $complaint->driver_id ?? '') }}">
            </div>
        </div>
    </div>

    {{-- Service Vehicle (visible only when yer = road) --}}
    <div class="col-md-12 mb-3" id="serviceVehicleField">
        <label for="service_vehicle_id" class="form-label fw-bold">
            🚐 {{ __('messages.complaints.service_vehicle') }}
            <span class="text-danger">*</span>
        </label>
        <select class="form-select" id="service_vehicle_id" name="service_vehicle_id"
                onchange="onServiceVehicleChange()">
            <option value="">{{ __('messages.complaints.service_vehicle_placeholder') }}</option>
            @foreach($serviceVehicles ?? [] as $vehicle)
                <option value="{{ $vehicle->id }}"
                    {{ old('service_vehicle_id', $complaint->service_vehicle_id ?? null) == $vehicle->id ? 'selected' : '' }}>
                    {{ $vehicle->name }}
                    @if($vehicle->plate_number) · {{ $vehicle->plate_number }} @endif
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('messages.complaints.service_vehicle_hint') }}</div>
        @if(($serviceVehicles ?? collect())->isEmpty())
            <div class="alert alert-warning mt-2 mb-0">
                <i class="bi bi-exclamation-triangle"></i>
                {{ __('messages.service_vehicles.no_vehicles') }}
                — <a href="{{ route('service-vehicles.create') }}">{{ __('messages.service_vehicles.new') }}</a>
            </div>
        @endif
    </div>

    {{-- Complaints / Service Type --}}
    <div class="col-md-12 mb-3" id="complaintsBlock">
        <label class="form-label fw-bold" id="complaintsLabel">📝 {{ __('messages.complaints.complaints_list') }}</label>

        {{-- Accident / Breakdown: complaint_types dropdown --}}
        <div id="complaintsDropdown">
            <div id="complaintsContainer">
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
        </div>

        {{-- Maintenance: service_km dropdown --}}
        <div id="serviceTypeBlock" style="display:none;">
            <select class="form-select" id="service_km_select" name="service_km" onchange="onServiceChange()">
                <option value="">{{ __('messages.complaints.select_service') }}</option>
            </select>
            <div class="form-text">{{ __('messages.complaints.service_hint') }}</div>
            <input type="hidden" name="complaints[]" id="serviceComplaintLabel"
                   value="{{ old('service_km_label') }}">
        </div>
    </div>

    {{-- KM --}}
    <div class="col-md-12 mb-3">
        <label for="km" class="form-label fw-bold">📊 {{ __('messages.complaints.km') }}</label>
        <input type="number" class="form-control input-readonly" id="km" name="km"
               value="{{ old('km', $complaint->km ?? '') }}" min="0"
               readonly>
    </div>

    {{-- Reported --}}
    <div class="col-md-12" id="bildirilmeFields">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">📅 {{ __('messages.complaints.reported_date') }}</label>
                <input type="date" class="form-control" name="reported_date"
                       value="{{ old('reported_date', isset($complaint->reported_date) && $complaint->reported_date ? \Carbon\Carbon::parse($complaint->reported_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">🕐 {{ __('messages.complaints.reported_time') }}</label>
                <input type="time" class="form-control" name="reported_time"
                       value="{{ old('reported_time', $complaint->reported_time ? \Carbon\Carbon::parse($complaint->reported_time)->format('H:i') : '') }}">
            </div>
        </div>
    </div>

    {{-- Start / End --}}
    <div class="col-md-12">
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">📅 {{ __('messages.complaints.start_date') }}</label>
                <input type="date" class="form-control" name="start_date"
                       value="{{ old('start_date', isset($complaint->start_date) && $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('Y-m-d') : $defaultStartDate) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">🕐 {{ __('messages.complaints.start_time') }}</label>
                <input type="time" class="form-control" name="start_time"
                       value="{{ old('start_time', $complaint->start_time ? \Carbon\Carbon::parse($complaint->start_time)->format('H:i') : $defaultStartTime) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">📅 {{ __('messages.complaints.end_date') }}</label>
                <input type="date" class="form-control" name="end_date"
                       value="{{ old('end_date', isset($complaint->end_date) && $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">🕐 {{ __('messages.complaints.end_time') }}</label>
                <input type="time" class="form-control" name="end_time"
                       value="{{ old('end_time', $complaint->end_time ? \Carbon\Carbon::parse($complaint->end_time)->format('H:i') : '') }}">
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="col-md-12 mb-3">
        <div class="row">
            <div class="col-md-6">
                <label for="status" class="form-label fw-bold">📊 {{ __('messages.common.status') }}</label>
                @php $currentStatus = old('status', $complaint->status?->value ?? 'pending'); @endphp
                <select class="form-select" id="status" name="status" required>
                    <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>
                        ⏳ {{ __('enums.complaint_status.pending') }}
                    </option>
                    <option value="in_progress" {{ $currentStatus === 'in_progress' ? 'selected' : '' }}>
                        🔨 {{ __('enums.complaint_status.in_progress') }}
                    </option>
                </select>
            </div>
        </div>
    </div>

    {{-- Parts --}}
    <div class="col-md-12 complaint-details-card p-3 mb-3">
        <h5 class="fw-bold mb-3">🔧 {{ __('messages.complaints.used_parts') }}</h5>
        <div id="detailsContainer">
            @if(count($detailsData) > 0)
                @foreach($detailsData as $index => $detail)
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
                                <input type="text" class="form-control input-disabled"
                                       name="details[{{ $index }}][name]"
                                       value="{{ $detail['name'] ?? '' }}" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label fw-bold stock-source-label">{{ __('messages.complaints.stock_qty') }}</label>
                                <input type="text" class="form-control input-disabled"
                                       name="details[{{ $index }}][stock_quantity]"
                                       value="{{ $detail['stock_quantity'] ?? '' }}" readonly>
                                <div class="form-text part-help"></div>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                                <input type="number" class="form-control"
                                       name="details[{{ $index }}][used_quantity]"
                                       value="{{ $detail['used_quantity'] ?? 1 }}" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">👤 {{ __('messages.complaints.employee') }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                           name="details[{{ $index }}][employee_code]"
                                           placeholder="{{ __('messages.employees.code_placeholder') }}"
                                           value="{{ $detail['employee_code'] ?? '' }}"
                                           oninput="getEmployeeByCode(this)"
                                           autocomplete="off"
                                           style="text-transform: uppercase;">
                                    <input type="text" class="form-control input-disabled" readonly tabindex="-1"
                                           name="details[{{ $index }}][employee_name]"
                                           value="{{ $detail['employee_name'] ?? '' }}">
                                    <input type="hidden" name="details[{{ $index }}][employee_id]"
                                           value="{{ $detail['employee_id'] ?? '' }}">
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
                            <input type="text" class="form-control" name="details[0][code]"
                                   placeholder="{{ __('messages.complaints.driver_placeholder') }}"
                                   oninput="getPartByCode(this)">
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
                            <input type="number" class="form-control" name="details[0][used_quantity]"
                                   min="0" value="1" required>
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

    {{-- Hidden — form partial communicates next detail index to create.blade.php --}}
    <input type="hidden" id="detailCountValue" value="{{ $detailCount }}">

    <div class="col-md-12 d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> {{ __('messages.common.save') }}
        </button>
        <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
    </div>
</div>
