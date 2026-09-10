<div class="row">
    {{-- Complaint Type --}}
    <div class="col-md-12 mb-3">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-bold">🏷️ {{ __('messages.complaints.complaint_type') }}</label>
                <div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="accident"
                               {{ ($complaint->complaint_type ?? '') === 'accident' ? 'checked' : '' }}>
                        <label class="form-check-label">🚗 {{ __('enums.complaint_type.accident') }}</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="breakdown"
                               {{ ($complaint->complaint_type ?? '') === 'breakdown' ? 'checked' : '' }}>
                        <label class="form-check-label">⚠️ {{ __('enums.complaint_type.breakdown') }}</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="maintenance"
                               {{ ($complaint->complaint_type ?? '') === 'maintenance' ? 'checked' : '' }}>
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
                <label>{{ __('messages.complaints.route_or_dqn') }}</label>
                <input type="text" class="form-control" id="route_number"
                       placeholder="{{ __('messages.complaints.route_or_dqn_placeholder') }}"
                       value="{{ $complaint->bus?->route_number ?? '' }}"
                       oninput="getBusByRoute(this.value)"
                       {{ isset($complaint->id) ? 'readonly style=background:#e9ecef;' : '' }}>
            </div>
            <div class="col-md-6">
                <label>{{ __('messages.buses.dqn') }}</label>
                <input type="text" class="form-control" id="dqn"
                       value="{{ $complaint->bus?->dqn ?? '' }}"
                       readonly style="background:#e9ecef;">
            </div>
        </div>
        <input type="hidden" name="bus_id" id="bus_id" value="{{ $complaint->bus_id ?? '' }}">
    </div>

    {{-- Location --}}
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">📍 {{ __('messages.complaints.location') }}</label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="yer" id="yer_road" value="road"
                       {{ ($complaint->yer ?? '') === 'road' ? 'checked' : '' }} onchange="toggleFields()">
                <label class="form-check-label" for="yer_road">🛣️ {{ __('enums.location.road') }}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="yer" id="yer_garage" value="garage"
                       {{ ($complaint->yer ?? 'garage') === 'garage' ? 'checked' : '' }} onchange="toggleFields()">
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

    {{-- Complaints --}}
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">📝 {{ __('messages.complaints.complaints_list') }}</label>
        <div id="complaintsContainer">
            @php
                $complaintsList = isset($complaint) && $complaint->items
                    ? $complaint->items->pluck('description')->toArray()
                    : [];
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
    </div>

    {{-- KM --}}
    <div class="col-md-12 mb-3">
        <label for="km" class="form-label fw-bold">📊 {{ __('messages.complaints.km') }}</label>
        <input type="number" class="form-control" id="km" name="km"
               value="{{ old('km', $complaint->km ?? '') }}" min="0"
               readonly style="background:#e9ecef;">
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
                       value="{{ old('reported_time', $complaint->reported_time ?? '') }}">
            </div>
        </div>
    </div>

    {{-- Start / End --}}
    <div class="col-md-12">
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">📅 {{ __('messages.complaints.start_date') }}</label>
                <input type="date" class="form-control" name="start_date"
                       value="{{ old('start_date', isset($complaint->start_date) && $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">🕐 {{ __('messages.complaints.start_time') }}</label>
                <input type="time" class="form-control" name="start_time"
                       value="{{ old('start_time', $complaint->start_time ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">📅 {{ __('messages.complaints.end_date') }}</label>
                <input type="date" class="form-control" name="end_date"
                       value="{{ old('end_date', isset($complaint->end_date) && $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">🕐 {{ __('messages.complaints.end_time') }}</label>
                <input type="time" class="form-control" name="end_time"
                       value="{{ old('end_time', $complaint->end_time ?? '') }}">
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="col-md-12 mb-3">
        <div class="row">
            <div class="col-md-6">
                <label for="status" class="form-label fw-bold">📊 {{ __('messages.common.status') }}</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="pending" {{ ($complaint->status ?? '') === 'pending' ? 'selected' : '' }}>
                        ⏳ {{ __('enums.complaint_status.pending') }}
                    </option>
                    <option value="in_progress" {{ ($complaint->status ?? '') === 'in_progress' ? 'selected' : '' }}>
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
            @php $detailsData = $details ?? []; @endphp
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
                                <label class="form-label fw-bold">{{ __('messages.complaints.stock_qty') }}</label>
                                <input type="text" class="form-control input-disabled"
                                       name="details[{{ $index }}][stock_quantity]"
                                       value="{{ $detail['stock_quantity'] ?? '' }}" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                                <input type="number" class="form-control"
                                       name="details[{{ $index }}][used_quantity]"
                                       value="{{ $detail['used_quantity'] ?? 1 }}" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">{{ __('messages.complaints.employee') }}</label>
                                <select class="form-select" name="details[{{ $index }}][employee_id]" required>
                                    <option value="">{{ __('messages.common.select') }}</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old("details.$index.employee_id", $detail['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name_with_position }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                                    <i class="bi bi-trash"></i> {{ __('messages.common.remove') }}
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
                            <label class="form-label fw-bold">{{ __('messages.complaints.stock_qty') }}</label>
                            <input type="text" class="form-control input-disabled" name="details[0][stock_quantity]" readonly>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                            <input type="number" class="form-control" name="details[0][used_quantity]"
                                   min="1" value="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ __('messages.complaints.employee') }}</label>
                            <select class="form-select" name="details[0][employee_id]" required>
                                <option value="">{{ __('messages.common.select') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name_with_position }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                                <i class="bi bi-trash"></i> {{ __('messages.common.remove') }}
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <label class="form-label fw-bold">📝 {{ __('messages.complaints.work_done_notes') }}</label>
                            <textarea class="form-control" name="details[0][notes]" rows="2"></textarea>
                        </div>
                    </div>
                    <hr>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addDetail()">
            <i class="bi bi-plus-circle"></i> {{ __('messages.complaints.add_part') }}
        </button>
    </div>

    <div class="col-md-12 d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> {{ __('messages.common.save') }}
        </button>
        <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
    </div>
</div>