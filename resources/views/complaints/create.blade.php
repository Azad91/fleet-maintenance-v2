@extends('layouts.app')

@section('title', __('messages.complaints.new'))

@section('content')
<div class="complaint-create-page">
    <div class="complaint-create-page__heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.complaints.eyebrow') }}</span>
            <h1>{{ __('messages.complaints.new_title') }}</h1>
            <p>{{ __('messages.complaints.new_subtitle') }}</p>
        </div>
        <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.complaints.back_to_cards') }}
        </a>
    </div>
    <div class="card complaint-create-card">
        <div class="card-header">
            <h4><i class="fas fa-screwdriver-wrench"></i> {{ __('messages.complaints.card_info') }}</h4>
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
            <form id="complaintForm" action="{{ route('complaints.store') }}" method="POST">
                @csrf
                @include('complaints.partials.form')
            </form>
        </div>
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

                // Texniki xidmət seçilmişdisə → intervalları yüklə
                const typeInput = document.querySelector('input[name="complaint_type"]:checked');
                if (typeInput && typeInput.value === 'maintenance') {
                    loadMotorOilIntervals();
                }
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
                    nameInput.value = data.driver_name || '';
                    idInput.value = data.driver_id;
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
    // PART (warehouse)
    // ═══════════════════════════════════════════════════════════════
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
            .catch(error => console.error('Part lookup error:', error));
    }

    // ═══════════════════════════════════════════════════════════════
    // COMPLAINT TYPE → LOCATION LOGIC
    // ═══════════════════════════════════════════════════════════════
    function handleComplaintTypeChange() {
        const typeInput = document.querySelector('input[name="complaint_type"]:checked');
        const roadRadio = document.getElementById('yer_road');
        const garageRadio = document.getElementById('yer_garage');

        if (!roadRadio || !garageRadio) return;

        // Yer sıfırlanır
        roadRadio.disabled = false;
        garageRadio.disabled = false;
        roadRadio.checked = false;
        garageRadio.checked = false;

        toggleFields();

        if (!typeInput) return;

        if (typeInput.value === 'maintenance') {
            roadRadio.disabled = true;
            garageRadio.checked = true;
            toggleFields();

            // Şikayətlər dropdown gizlət, Xidmət Növü göstər
            document.getElementById('complaintsDropdown').style.display = 'none';
            document.getElementById('serviceTypeBlock').style.display = 'block';
            document.getElementById('complaintsLabel').innerHTML = '📝 ' + @json(__('messages.complaints.service_type_label'));

            // Bus artıq seçilibsə, intervalları yüklə
            if (document.getElementById('bus_id').value) {
                loadMotorOilIntervals();
            }
        } else {
            // Şikayətlər dropdown göstər, Xidmət Növü gizlət
            document.getElementById('complaintsDropdown').style.display = 'block';
            document.getElementById('serviceTypeBlock').style.display = 'none';
            document.getElementById('complaintsLabel').innerHTML = '📝 ' + @json(__('messages.complaints.complaints_list'));

            // Service dəyərlərini sıfırla
            document.getElementById('service_km_select').innerHTML = '<option value="">' + @json(__('messages.complaints.select_service')) + '</option>';
            document.getElementById('serviceComplaintLabel').value = '';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // MOTOR OIL
    // ═══════════════════════════════════════════════════════════════
    function loadMotorOilIntervals() {
        const busId = document.getElementById('bus_id').value;
        const select = document.getElementById('service_km_select');

        if (!busId) {
            select.innerHTML = '<option value="">' + @json(__('messages.complaints.select_service')) + '</option>';
            return;
        }

        select.innerHTML = '<option value="">' + @json(__('messages.common.loading')) + '</option>';

        fetch('/get-motor-oil-intervals/' + busId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            let html = '<option value="">' + @json(__('messages.complaints.select_service')) + '</option>';

            if (data.intervals && data.intervals.length > 0) {
                data.intervals.forEach(item => {
                    html += `<option value="${item.km}">${item.label}</option>`;
                });
            } else {
                html = '<option value="">' + @json(__('messages.complaints.no_intervals')) + '</option>';
            }

            select.innerHTML = html;
        })
        .catch(err => {
            console.error('Interval load error:', err);
            select.innerHTML = '<option value="">' + @json(__('messages.complaints.select_service')) + '</option>';
        });
    }

    function onServiceChange() {
        const select = document.getElementById('service_km_select');
        const km = select.value;
        const busId = document.getElementById('bus_id').value;
        const labelInput = document.getElementById('serviceComplaintLabel');

        // Hidden complaints[] dəyərini doldur
        if (km && select.selectedIndex >= 0) {
            labelInput.value = select.options[select.selectedIndex].text;
        } else {
            labelInput.value = '';
        }

        // Detalları sıfırla
        const detailsContainer = document.getElementById('detailsContainer');
        detailsContainer.innerHTML = '';

        if (!km || !busId) return;

        fetch('/get-motor-oil-parts/' + busId + '?km=' + encodeURIComponent(km), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            renderServiceParts(data.parts || []);
        })
        .catch(err => {
            console.error('Parts load error:', err);
        });
    }

    function renderServiceParts(parts) {
        const container = document.getElementById('detailsContainer');

        if (!parts || parts.length === 0) {
            container.innerHTML = `
                <div class="alert alert-warning mb-0">
                    ${@json(__('messages.complaints.no_parts_for_interval'))}
                </div>
            `;
            return;
        }

        let html = '';
        parts.forEach((part, idx) => {
            html += `
                <div class="detail-item">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">${@json(__('messages.complaints.related_complaint'))}</label>
                            <select class="form-select" name="details[${idx}][shikayet_index]">
                                <option value="0">1</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">${@json(__('messages.complaints.part_code'))}</label>
                            <input type="text" class="form-control" name="details[${idx}][code]"
                                   value="${part.part_code}" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">${@json(__('messages.complaints.part_name'))}</label>
                            <input type="text" class="form-control input-disabled" name="details[${idx}][name]"
                                   value="${part.part_name}" readonly>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold">${@json(__('messages.complaints.stock_qty'))}</label>
                            <input type="text" class="form-control input-disabled" name="details[${idx}][stock_quantity]"
                                   value="${part.stock_quantity}" readonly>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold">${@json(__('messages.complaints.used_qty'))}</label>
                            <input type="number" class="form-control" name="details[${idx}][used_quantity]"
                                   value="${part.quantity}" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">👤 ${@json(__('messages.complaints.employee'))}</label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       name="details[${idx}][employee_code]"
                                       placeholder="${@json(__('messages.employees.code_placeholder'))}"
                                       oninput="getEmployeeByCode(this)"
                                       autocomplete="off"
                                       style="text-transform: uppercase;">
                                <input type="text" class="form-control input-disabled" readonly tabindex="-1"
                                       name="details[${idx}][employee_name]">
                                <input type="hidden" name="details[${idx}][employee_id]">
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
                            <label class="form-label fw-bold">📝 ${@json(__('messages.complaints.work_done_notes'))}</label>
                            <textarea class="form-control" name="details[${idx}][notes]" rows="2"></textarea>
                        </div>
                    </div>
                    <hr>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // ═══════════════════════════════════════════════════════════════
    // COMPLAINTS (add / remove)
    // ═══════════════════════════════════════════════════════════════
    function addComplaint() {
        const container = document.getElementById('complaintsContainer');
        const source = container.querySelector('.complaint-item');
        if (!source) return;

        const item = source.cloneNode(true);
        const select = item.querySelector('select');
        if (select) select.value = '';

        container.append(item);
        container.querySelectorAll('.complaint-number').forEach((el, idx) => {
            el.textContent = (idx + 1) + '.';
        });
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

    // ═══════════════════════════════════════════════════════════════
    // DETAILS (add / remove)
    // ═══════════════════════════════════════════════════════════════
    function addDetail() {
        const container = document.getElementById('detailsContainer');
        const source = container.querySelector('.detail-item');

        // Texniki xidmətdə manual əlavə bloklanır (avtomatik dolur)
        const typeInput = document.querySelector('input[name="complaint_type"]:checked');
        if (typeInput && typeInput.value === 'maintenance') {
            return;
        }

        if (!source) return;

        const item = source.cloneNode(true);
        item.querySelectorAll('input:not([type="hidden"])').forEach(i => i.value = '');
        item.querySelectorAll('textarea').forEach(t => t.value = '');
        item.querySelectorAll('input[type="hidden"]').forEach(i => i.value = '');

        if (item.querySelector('input[name*="[used_quantity]"]')) {
            item.querySelector('input[name*="[used_quantity]"]').value = '1';
        }

        const newIndex = container.querySelectorAll('.detail-item').length;
        item.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.name) {
                el.name = el.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            }
        });

        container.append(item);
    }

    function removeDetail(button) {
        const items = document.querySelectorAll('.detail-item');
        if (items.length > 1) {
            button.closest('.detail-item').remove();
        } else {
            // Son detalı da silə bilər (used_quantity = 0 olacaq)
            const item = button.closest('.detail-item');
            item.querySelectorAll('input:not([type="hidden"])').forEach(i => {
                if (i.name && i.name.includes('used_quantity')) {
                    i.value = '0';
                }
            });
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // LOCATION (Yol / Qaraj → driver visibility)
    // ═══════════════════════════════════════════════════════════════
    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked');
        const driverField = document.getElementById('surucuField');
        const reportFields = document.getElementById('bildirilmeFields');

        if (!yer) {
            if (driverField) driverField.style.display = 'none';
            if (reportFields) reportFields.style.display = 'none';
            return;
        }

        if (yer.value === 'garage') {
            if (driverField) driverField.style.display = 'none';
            if (reportFields) reportFields.style.display = 'none';
        } else {
            if (driverField) driverField.style.display = 'block';
            if (reportFields) reportFields.style.display = 'block';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        handleComplaintTypeChange();
        toggleFields();
    });
</script>
@endsection
