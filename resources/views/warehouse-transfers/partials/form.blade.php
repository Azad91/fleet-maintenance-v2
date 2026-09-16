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

    {{-- ─── Transfer Type ─── --}}
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

    {{-- ─── Destination: Garage ─── --}}
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

    {{-- ─── Destination: Service Vehicle ─── --}}
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

    {{-- ─── Notes ─── --}}
    <div class="col-md-12">
        <label for="notes" class="form-label fw-bold">
            {{ __('messages.transfers.notes') }}
        </label>
        <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
    </div>

    {{-- ─── Items ─── --}}
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
                            <th style="width: 45%;">{{ __('messages.transfers.item_code') }}</th>
                            <th style="width: 20%;">{{ __('messages.transfers.available_qty') }}</th>
                            <th style="width: 20%;">{{ __('messages.transfers.declared_qty') }}</th>
                            <th style="width: 15%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer">
                        {{-- rows injected by JS --}}
                    </tbody>
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

@push('scripts')
<script>
    // ─── Items catalog from server ───
    const WAREHOUSE_ITEMS = @json($warehouses->map(fn($w) => [
        'id' => $w->id,
        'code' => $w->code,
        'name' => $w->name,
        'quantity' => $w->quantity,
        'unit' => $w->unit,
    ]));

    const TRANSLATIONS = {
        select: @json(__('messages.common.select')),
        available: @json(__('messages.transfers.available_qty')),
    };

    let itemCounter = 0;

    function addItemRow(selectedId = '', declaredQty = '') {
        const container = document.getElementById('itemsContainer');
        if (!container) return;

        const idx = itemCounter++;

        let optionsHtml = `<option value="">${TRANSLATIONS.select}</option>`;
        WAREHOUSE_ITEMS.forEach(item => {
            const selected = String(item.id) === String(selectedId) ? 'selected' : '';
            optionsHtml += `<option value="${item.id}" data-qty="${item.quantity}" data-name="${item.name}" data-unit="${item.unit ?? ''}" ${selected}>${item.code} — ${item.name}</option>`;
        });

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select class="form-select form-select-sm item-select"
                        name="items[${idx}][warehouse_id]"
                        onchange="onItemSelected(this)"
                        required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <span class="text-muted item-available">—</span>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm"
                       name="items[${idx}][declared_quantity]"
                       min="1" value="${declaredQty}" required>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="removeItemRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        container.appendChild(row);

        // Trigger initial "available" display if a value is preselected
        const sel = row.querySelector('.item-select');
        if (selectedId) onItemSelected(sel);
    }

    function removeItemRow(btn) {
        const container = document.getElementById('itemsContainer');
        if (container.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
    }

    function onItemSelected(selectEl) {
        const row = selectEl.closest('tr');
        const opt = selectEl.options[selectEl.selectedIndex];
        const available = row.querySelector('.item-available');

        if (! selectEl.value) {
            available.textContent = '—';
            return;
        }

        const qty = opt.dataset.qty ?? '0';
        const unit = opt.dataset.unit ?? '';
        available.innerHTML = `<strong>${qty}</strong> ${unit}`;
    }

    // ─── Type switch ───
    function onTypeChange() {
        const type = document.querySelector('input[name="type"]:checked')?.value;

        const garageField   = document.getElementById('to_garage_field');
        const vehicleField  = document.getElementById('to_service_vehicle_field');
        const garageSelect  = document.getElementById('to_garage_id');
        const vehicleSelect = document.getElementById('to_service_vehicle_id');

        // Hide both by default
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
        // return_to_quarantine: both hidden, no destination needed.
    }

    document.addEventListener('DOMContentLoaded', function () {
        onTypeChange();

        // Seed one empty row so the form is usable immediately.
        addItemRow();
    });
</script>
@endpush
