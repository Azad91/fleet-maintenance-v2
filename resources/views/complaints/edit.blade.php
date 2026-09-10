@extends('layouts.app')

@section('title', __('messages.complaints.edit_title'))

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

            <div class="mb-3">
                <label class="form-label fw-bold">🚌 {{ __('messages.complaints.bus') }}</label>
                <div class="row">
                    <div class="col-md-6">
                        <label>{{ __('messages.buses.route_number') }}</label>
                        <input type="text" class="form-control" value="{{ $complaint->bus->route_number ?? '' }}" readonly style="background:#e9ecef;">
                    </div>
                    <div class="col-md-6">
                        <label>{{ __('messages.buses.dqn') }}</label>
                        <input type="text" class="form-control" value="{{ $complaint->bus->dqn ?? '' }}" readonly style="background:#e9ecef;">
                    </div>
                </div>
                <input type="hidden" name="bus_id" value="{{ $complaint->bus_id }}">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">📍 {{ __('messages.complaints.location') }}</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="yer" id="yer_road" value="road"
                               {{ $complaint->yer === 'road' ? 'checked' : '' }} onchange="toggleFields()">
                        <label class="form-check-label" for="yer_road">🛣️ {{ __('enums.location.road') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="yer" id="yer_garage" value="garage"
                               {{ $complaint->yer === 'garage' ? 'checked' : '' }} onchange="toggleFields()">
                        <label class="form-check-label" for="yer_garage">🏠 {{ __('enums.location.garage') }}</label>
                    </div>
                </div>
            </div>

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

            <div class="mb-3">
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
            </div>

            <div class="mb-3">
                <label for="km" class="form-label fw-bold">📊 {{ __('messages.complaints.km') }}</label>
                <input type="number" class="form-control" id="km" name="km"
                       value="{{ old('km', $complaint->km) }}" min="0" readonly style="background:#e9ecef;">
            </div>

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
                               value="{{ old('reported_time', $complaint->reported_time) }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">📅 {{ __('messages.complaints.start_date') }}</label>
                    <input type="date" class="form-control" name="start_date"
                           value="{{ old('start_date', $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">🕐 {{ __('messages.complaints.start_time') }}</label>
                    <input type="time" class="form-control" name="start_time"
                           value="{{ old('start_time', $complaint->start_time) }}">
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
                           value="{{ old('end_time', $complaint->end_time) }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label fw-bold">📊 {{ __('messages.common.status') }}</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="pending" {{ $complaint->status === 'pending' ? 'selected' : '' }}>
                        ⏳ {{ __('enums.complaint_status.pending') }}
                    </option>
                    <option value="in_progress" {{ $complaint->status === 'in_progress' ? 'selected' : '' }}>
                        🔨 {{ __('enums.complaint_status.in_progress') }}
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">🏷️ {{ __('messages.complaints.complaint_type') }}</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="complaint_type" value="accident"
                               {{ $complaint->complaint_type === 'accident' ? 'checked' : '' }}>
                        <label class="form-check-label">🚗 {{ __('enums.complaint_type.accident') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="complaint_type" value="breakdown"
                               {{ $complaint->complaint_type === 'breakdown' ? 'checked' : '' }}>
                        <label class="form-check-label">⚠️ {{ __('enums.complaint_type.breakdown') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="complaint_type" value="maintenance"
                               {{ $complaint->complaint_type === 'maintenance' ? 'checked' : '' }}>
                        <label class="form-check-label">🔧 {{ __('enums.complaint_type.maintenance') }}</label>
                    </div>
                </div>
            </div>

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
                                            @foreach($complaintsList as $i => $c)
                                                <option value="{{ $i }}" {{ ($detail['shikayet_index'] ?? 0) == $i ? 'selected' : '' }}>
                                                    {{ trim($c) }}
                                                </option>
                                            @endforeach
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
                                        <label class="form-label fw-bold">{{ __('messages.complaints.stock_qty') }}</label>
                                        <input type="text" class="form-control input-disabled" name="details[{{ $index }}][stock_quantity]"
                                               value="{{ $detail['stock_quantity'] ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                                        <input type="number" class="form-control" name="details[{{ $index }}][used_quantity]"
                                               value="{{ $detail['used_quantity'] ?? 1 }}" min="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">{{ __('messages.complaints.employee') }}</label>
                                        <select class="form-select" name="details[{{ $index }}][employee_id]" required>
                                            <option value="">{{ __('messages.common.select') }}</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}" {{ old("details.$index.employee_id", $detail['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
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
                                    <label class="form-label fw-bold">{{ __('messages.complaints.stock_qty') }}</label>
                                    <input type="text" class="form-control input-disabled" name="details[0][stock_quantity]" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                                    <input type="number" class="form-control" name="details[0][used_quantity]" min="1" value="1" required>
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
    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked');
        if (!yer) return;

        const driverField = document.getElementById('surucuField');
        const reportFields = document.getElementById('bildirilmeFields');

        if (yer.value === 'garage') {
            if (driverField) driverField.style.display = 'none';
            if (reportFields) reportFields.style.display = 'none';
        } else {
            if (driverField) driverField.style.display = 'block';
            if (reportFields) reportFields.style.display = 'block';
        }
    }

    function addComplaint() {
        const container = document.getElementById('complaintsContainer');
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
        if (container.querySelectorAll('.complaint-item').length > 1) {
            button.closest('.complaint-item').remove();
            container.querySelectorAll('.complaint-number').forEach((el, idx) => {
                el.textContent = (idx + 1) + '.';
            });
        }
    }

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
                    <label class="form-label fw-bold">{{ __('messages.complaints.stock_qty') }}</label>
                    <input type="text" class="form-control input-disabled" name="details[${detailCount}][stock_quantity]" readonly>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">{{ __('messages.complaints.used_qty') }}</label>
                    <input type="number" class="form-control" name="details[${detailCount}][used_quantity]" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">{{ __('messages.complaints.employee') }}</label>
                    <select class="form-select" name="details[${detailCount}][employee_id]" required>
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
        }
    }

    function getPartByCode(input) {
        const code = input.value;
        const item = input.closest('.detail-item');
        const nameInput = item.querySelector('input[name*="[name]"]');
        const stockInput = item.querySelector('input[name*="[stock_quantity]"]');

        if (!code) {
            nameInput.value = '';
            stockInput.value = '';
            return;
        }

        fetch('/get-detal-by-kod/' + encodeURIComponent(code))
            .then(response => response.json())
            .then(data => {
                nameInput.value = data.detallar_name || '';
                stockInput.value = data.stock_quantity || '';
            })
            .catch(error => console.error('Error:', error));
    }

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

    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();
    });
</script>
@endsection