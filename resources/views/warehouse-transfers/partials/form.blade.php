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

    <div class="col-md-12">
        <label class="form-label fw-bold">
            {{ __('messages.transfers.type') }} <span class="text-danger">*</span>
        </label>
        <div>
            @foreach(\App\Enums\TransferType::cases() as $type)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio"
                           name="type"
                           id="type_{{ $type->value }}"
                           value="{{ $type->value }}"
                           {{ old('type', 'garage_to_garage') === $type->value ? 'checked' : '' }}
                           onchange="onTypeChange()">
                    <label class="form-check-label" for="type_{{ $type->value }}">
                        {{ $type->label() }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-md-6" id="to_garage_field">
        <label for="to_garage_id" class="form-label fw-bold">
            {{ __('messages.transfers.to_garage') }} <span class="text-danger">*</span>
        </label>
        <select class="form-select" id="to_garage_id" name="to_garage_id">
            <option value="">{{ __('messages.common.select') }}</option>
            @foreach($otherGarages as $garage)
                <option value="{{ $garage->id }}"
                    {{ old('to_garage_id') == $garage->id ? 'selected' : '' }}>
                    {{ $garage->name }} · {{ $garage->code }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6" id="to_service_vehicle_field" style="display:none;">
        <label for="to_service_vehicle_id" class="form-label fw-bold">
            {{ __('messages.transfers.to_service_vehicle') }} <span class="text-danger">*</span>
        </label>
        <select class="form-select" id="to_service_vehicle_id" name="to_service_vehicle_id">
            <option value="">{{ __('messages.common.select') }}</option>
            @foreach($serviceVehicles as $vehicle)
                <option value="{{ $vehicle->id }}"
                    {{ old('to_service_vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                    {{ $vehicle->name }}
                    @if($vehicle->plate_number) · {{ $vehicle->plate_number }} @endif
                </option>
            @endforeach
        </select>
        @if($serviceVehicles->isEmpty())
            <small class="text-danger d-block mt-1">
                {{ __('messages.service_vehicles.no_vehicles') }}
                — <a href="{{ route('service-vehicles.create') }}">{{ __('messages.service_vehicles.new') }}</a>
            </small>
        @endif
    </div>

    <div class="col-md-12">
        <label for="notes" class="form-label fw-bold">
            {{ __('messages.transfers.notes') }}
        </label>
        <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
    </div>

    <div class="col-md-12">
        <hr class="my-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">📦 {{ __('messages.transfers.items') }}</h5>
            <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow()">
                <i class="bi bi-plus-lg"></i> {{ __('messages.transfers.add_item') }}
            </button>
        </div>

        @if($warehouses->isEmpty())
            <div class="alert alert-warning mb-0">
                {{ __('messages.warehouse.no_items') }}
                — <a href="{{ route('warehouses.create') }}">{{ __('messages.warehouse.new') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">{{ __('messages.transfers.item_code') }}</th>
                            <th style="width: 30%;">{{ __('messages.complaints.part_name') }}</th>
                            <th style="width: 15%;">{{ __('messages.transfers.available_qty') }}</th>
                            <th style="width: 15%;">{{ __('messages.transfers.declared_qty') }}</th>
                            <th style="width: 15%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer"></tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> {{ $submitLabel }}
    </button>
    <a href="{{ route('warehouse-transfers.index') }}" class="btn btn-secondary">
        {{ __('messages.common.cancel') }}
    </a>
</div>

@php
    $warehouseItemsPayload = $warehouses->map(function ($w) {
        return [
            'id'       => $w->id,
            'code'     => $w->code,
            'name'     => $w->name,
            'quantity' => $w->quantity,
            'unit'     => $w->unit,
        ];
    })->values()->all();
@endphp

@push('scripts')
<script>
    const WAREHOUSE_ITEMS = @json($warehouseItemsPayload);

    // Build a lookup map for O(1) code matching.
    const ITEM_BY_CODE = {};
    WAREHOUSE_ITEMS.forEach(function (item) {
        ITEM_BY_CODE[String(item.code).toUpperCase()] = item;
    });

    let itemCounter = 0;

    function addItemRow() {
        const container = document.getElementById('itemsContainer');
        if (!container) return;

        const idx = itemCounter++;

        const row = document.createElement('tr');
        row.innerHTML =
            '<td>' +
                '<input type="text" class="form-control form-control-sm item-code" ' +
                       'name="items[' + idx + '][code]" ' +
                       'placeholder="{{ __('messages.warehouse.code_placeholder') }}" ' +
                       'oninput="onCodeInput(this)" ' +
                       'autocomplete="off" ' +
                       'style="text-transform: uppercase;" ' +
                       'required>' +
                '<input type="hidden" name="items[' + idx + '][warehouse_id]" class="item-warehouse-id">' +
            '</td>' +
            '<td>' +
                '<input type="text" class="form-control form-control-sm item-name" readonly tabindex="-1" style="background:#e9ecef;">' +
            '</td>' +
            '<td>' +
                '<span class="text-muted item-available">—</span>' +
            '</td>' +
            '<td>' +
                '<input type="number" class="form-control form-control-sm item-qty" ' +
                       'name="items[' + idx + '][declared_quantity]" ' +
                       'min="1" required>' +
            '</td>' +
            '<td class="text-end">' +
                '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</td>';

        container.appendChild(row);
    }

    function removeItemRow(btn) {
        const container = document.getElementById('itemsContainer');
        if (container.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
    }

    function onCodeInput(input) {
        const row = input.closest('tr');
        const code = String(input.value || '').trim().toUpperCase();
        const item = ITEM_BY_CODE[code];

        const nameEl     = row.querySelector('.item-name');
        const availableEl = row.querySelector('.item-available');
        const qtyEl      = row.querySelector('.item-qty');
        const idEl       = row.querySelector('.item-warehouse-id');

        input.classList.remove('is-valid', 'is-invalid');

        if (!code) {
            nameEl.value = '';
            availableEl.textContent = '—';
            idEl.value = '';
            return;
        }

        if (!item) {
            nameEl.value = '';
            availableEl.textContent = '—';
            idEl.value = '';
            input.classList.add('is-invalid');
            return;
        }

        // Match found — autofill name, available, and warehouse id.
        nameEl.value = item.name;
        availableEl.innerHTML = '<strong>' + item.quantity + '</strong> ' + (item.unit || '');
        idEl.value = item.id;
        input.classList.add('is-valid');

        if (!qtyEl.value) {
            qtyEl.value = 1;
        }
    }

    function onTypeChange() {
        const type = document.querySelector('input[name="type"]:checked')?.value;

        const garageField   = document.getElementById('to_garage_field');
        const vehicleField  = document.getElementById('to_service_vehicle_field');
        const garageSelect  = document.getElementById('to_garage_id');
        const vehicleSelect = document.getElementById('to_service_vehicle_id');

        garageField.style.display   = 'none';
        vehicleField.style.display  = 'none';
        garageSelect.disabled  = true;
        vehicleSelect.disabled = true;
        garageSelect.value     = '';
        vehicleSelect.value    = '';

        if (type === 'garage_to_garage') {
            garageField.style.display = 'block';
            garageSelect.disabled = false;
        } else if (type === 'to_service_vehicle') {
            vehicleField.style.display = 'block';
            vehicleSelect.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        onTypeChange();
        addItemRow();
    });
</script>
@endpush
