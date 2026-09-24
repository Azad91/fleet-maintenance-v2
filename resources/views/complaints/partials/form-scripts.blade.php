{{-- ─────────────────────────────────────────────────────────────── --}}
{{-- Shared scripts for the complaint create / edit form.          --}}
{{--                                                                --}}
{{-- Extracted from create.blade.php so both views can include the  --}}
{{-- same behavior without duplicating ~250 lines of JavaScript.    --}}
{{--                                                                --}}
{{-- Expectations: the view including this partial must have the    --}}
{{-- complaint form partial rendered FIRST, because the scripts     --}}
{{-- query DOM elements that only exist after the form is on the    --}}
{{-- page (e.g. #dqn, #detailsContainer, #service_vehicle_id).      --}}
{{-- ─────────────────────────────────────────────────────────────── --}}

<script>
    // ═══════════════════════════════════════════════════════════════
    // BUS
    // ═══════════════════════════════════════════════════════════════
    let busLookupRequest = 0;

    function getBusByDqn(dqnValue) {
        const input       = document.getElementById('dqn');
        const routeInput  = document.getElementById('route_number');
        const busIdInput  = document.getElementById('bus_id');
        const kmInput     = document.getElementById('km');
        const help        = document.getElementById('dqnHelp');

        const dqn = (dqnValue || '').trim().toUpperCase();

        routeInput.value = '';
        busIdInput.value = '';
        kmInput.value    = '';
        help.textContent = '';
        help.className   = 'form-text';
        input.classList.remove('is-valid', 'is-invalid');

        if (! dqn) return;

        const requestId = ++busLookupRequest;

        fetch('/get-bus-by-dqn/' + encodeURIComponent(dqn), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (requestId !== busLookupRequest) return;

            if (data.found) {
                routeInput.value = data.route_number || '';
                busIdInput.value = data.bus_id || '';
                kmInput.value    = data.km || '';
                input.classList.add('is-valid');
                help.textContent = '';
                help.className   = 'form-text';

                const typeInput = document.querySelector('input[name="complaint_type"]:checked');
                if (typeInput && typeInput.value === 'maintenance') {
                    loadMotorOilIntervals();
                }
            } else {
                input.classList.add('is-invalid');
                help.textContent = @json(__('messages.complaints.dqn_not_found'));
                help.className   = 'form-text text-danger';
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
        const nameInput      = document.getElementById('driver_name');
        const idInput        = document.getElementById('driver_id');
        const help           = document.getElementById('driverHelp');
        const codeInput      = document.getElementById('driver_code');

        idInput.value   = '';
        nameInput.value = '';
        codeInput.classList.remove('is-valid', 'is-invalid');

        if (! normalizedCode) {
            help.textContent = @json(__('messages.complaints.driver_help_default'));
            help.className   = 'form-text';

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
                    idInput.value   = data.driver_id;
                    codeInput.classList.add('is-valid');
                    help.textContent = @json(__('messages.complaints.driver_found'));
                    help.className   = 'form-text text-success';
                } else {
                    codeInput.classList.add('is-invalid');
                    help.textContent = @json(__('messages.complaints.driver_not_found'));
                    help.className   = 'form-text text-danger';
                }
            });
    }

    // ═══════════════════════════════════════════════════════════════
    // EMPLOYEE
    // ═══════════════════════════════════════════════════════════════
    let employeeLookupRequest = 0;

    function getEmployeeByCode(input) {
        const code      = input.value.trim().toUpperCase();
        const item      = input.closest('.detail-item');
        const nameInput = item.querySelector('input[name*="[employee_name]"]');
        const idInput   = item.querySelector('input[name*="[employee_id]"]');

        nameInput.value = '';
        idInput.value   = '';
        input.classList.remove('is-valid', 'is-invalid');

        if (! code) return;

        const requestId = ++employeeLookupRequest;

        fetch('/get-employee-by-kod/' + encodeURIComponent(code), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (requestId !== employeeLookupRequest) return;

            if (data.found) {
                nameInput.value = data.employee_name || '';
                idInput.value   = data.employee_id || '';
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
        const code      = input.value.trim();
        const item      = input.closest('.detail-item');
        const nameInput = item.querySelector('input[name*="[name]"]');
        const stockInput = item.querySelector('input[name*="[stock_quantity]"]');
        const helpEl    = item.querySelector('.part-help');

        nameInput.value  = '';
        stockInput.value = '';
        input.classList.remove('is-valid', 'is-invalid');

        if (helpEl) {
            helpEl.textContent = '';
            helpEl.className   = 'form-text part-help';
        }

        if (! code) return;

        const yer = document.querySelector('input[name="yer"]:checked')?.value;

        // ─── ROAD: source is the selected service vehicle ───
        if (yer === 'road') {
            const vehicleId = document.getElementById('service_vehicle_id')?.value;

            if (! vehicleId) {
                input.classList.add('is-invalid');

                if (helpEl) {
                    helpEl.textContent = @json(__('messages.complaints.select_vehicle_first'));
                    helpEl.className   = 'form-text text-danger part-help';
                }

                return;
            }

            fetch('/get-service-vehicle-part-by-code?service_vehicle_id='
                    + encodeURIComponent(vehicleId)
                    + '&code=' + encodeURIComponent(code), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
            .then(response => response.json())
            .then(data => {
                if (data.found) {
                    nameInput.value  = data.part_name || '';
                    stockInput.value = data.stock_quantity ?? 0;
                    input.classList.add('is-valid');

                    if (helpEl) {
                        helpEl.textContent = @json(__('messages.complaints.service_vehicle'))
                            + ': ' + (data.unit || '');
                        helpEl.className = 'form-text text-success part-help';
                    }
                } else {
                    input.classList.add('is-invalid');

                    if (helpEl) {
                        helpEl.textContent = @json(__('messages.complaints.part_not_on_vehicle'));
                        helpEl.className   = 'form-text text-danger part-help';
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
                nameInput.value  = data.detallar_name || '';
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
    // COMPLAINT TYPE → LOCATION
    //
    // `resetLocation` — false on page load (so old('yer') survives),
    // true when the user changes a radio (so the location resets).
    // ═══════════════════════════════════════════════════════════════
    function handleComplaintTypeChange(resetLocation) {
        if (typeof resetLocation === 'undefined') {
            resetLocation = true;
        }

        const typeInput   = document.querySelector('input[name="complaint_type"]:checked');
        const roadRadio   = document.getElementById('yer_road');
        const garageRadio = document.getElementById('yer_garage');

        if (! roadRadio || ! garageRadio) return;

        roadRadio.disabled   = false;
        garageRadio.disabled = false;

        if (resetLocation) {
            roadRadio.checked   = false;
            garageRadio.checked = false;
        }

        toggleFields();

        if (! typeInput) return;

        if (typeInput.value === 'maintenance') {
            roadRadio.disabled   = true;
            garageRadio.checked  = true;
            toggleFields();

            document.getElementById('complaintsDropdown').style.display = 'none';
            document.getElementById('serviceTypeBlock').style.display    = 'block';
            document.getElementById('complaintsLabel').innerHTML =
                '📝 ' + @json(__('messages.complaints.service_type_label'));

            document.querySelectorAll('#complaintsDropdown select[name="complaints[]"]')
                .forEach(el => { el.disabled = true; el.required = false; });

            document.querySelectorAll('#serviceTypeBlock select, #serviceTypeBlock input')
                .forEach(el => { el.disabled = false; });

            if (document.getElementById('bus_id').value) {
                loadMotorOilIntervals();
            }
        } else {
            document.getElementById('complaintsDropdown').style.display = 'block';
            document.getElementById('serviceTypeBlock').style.display    = 'none';
            document.getElementById('complaintsLabel').innerHTML =
                '📝 ' + @json(__('messages.complaints.complaints_list'));

            document.querySelectorAll('#complaintsDropdown select[name="complaints[]"]')
                .forEach(el => { el.disabled = false; el.required = true; });

            document.querySelectorAll('#serviceTypeBlock select, #serviceTypeBlock input')
                .forEach(el => { el.disabled = true; });

            document.getElementById('service_km_select').innerHTML =
                '<option value="">' + @json(__('messages.complaints.select_service')) + '</option>';

            document.getElementById('serviceComplaintLabel').value = '';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // MOTOR OIL
    // ═══════════════════════════════════════════════════════════════
    function loadMotorOilIntervals() {
        const busId  = document.getElementById('bus_id').value;
        const select = document.getElementById('service_km_select');

        if (! busId) {
            select.innerHTML = '<option value="">'
                + @json(__('messages.complaints.select_service')) + '</option>';

            return;
        }

        select.innerHTML = '<option value="">'
            + @json(__('messages.common.loading')) + '</option>';

        fetch('/get-motor-oil-intervals/' + busId, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            let html = '<option value="">'
                + @json(__('messages.complaints.select_service')) + '</option>';

            if (data.intervals && data.intervals.length > 0) {
                data.intervals.forEach(item => {
                    html += `<option value="${item.km}">${item.label}</option>`;
                });
            } else {
                html = '<option value="">'
                    + @json(__('messages.complaints.no_intervals')) + '</option>';
            }

            select.innerHTML = html;
        })
        .catch(err => {
            console.error('Interval load error:', err);
            select.innerHTML = '<option value="">'
                + @json(__('messages.complaints.select_service')) + '</option>';
        });
    }

    function onServiceChange() {
        const select     = document.getElementById('service_km_select');
        const km         = select.value;
        const busId      = document.getElementById('bus_id').value;
        const labelInput = document.getElementById('serviceComplaintLabel');

        if (km && select.selectedIndex >= 0) {
            labelInput.value = select.options[select.selectedIndex].text;
        } else {
            labelInput.value = '';
        }

        const detailsContainer = document.getElementById('detailsContainer');
        detailsContainer.innerHTML = '';

        if (! km || ! busId) return;

        fetch('/get-motor-oil-parts/' + busId + '?km=' + encodeURIComponent(km), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
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

        if (! parts || parts.length === 0) {
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
        if (! container) return;

        const source = container.querySelector('.complaint-item');
        if (! source) return;

        const item   = source.cloneNode(true);
        const select = item.querySelector('select');

        if (select) select.value = '';

        container.append(item);

        container.querySelectorAll('.complaint-number').forEach((el, idx) => {
            el.textContent = (idx + 1) + '.';
        });
    }

    function removeComplaint(button) {
        const container = document.getElementById('complaintsContainer');
        if (! container) return;

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
    // Starting index coming from the server (max key in old input + 1)
    let detailCount = parseInt(
        document.getElementById('detailCountValue')?.value || '1',
        10
    ) || 1;

    function addDetail() {
        const container = document.getElementById('detailsContainer');
        if (! container) return;

        const source = container.querySelector('.detail-item');
        if (! source) return;

        const typeInput = document.querySelector('input[name="complaint_type"]:checked');
        if (typeInput && typeInput.value === 'maintenance') {
            return;
        }

        const item = source.cloneNode(true);

        item.querySelectorAll('input:not([type="hidden"])').forEach(i => { i.value = ''; });
        item.querySelectorAll('textarea').forEach(t => { t.value = ''; });
        item.querySelectorAll('input[type="hidden"]').forEach(i => { i.value = ''; });

        const qtyInput = item.querySelector('input[name*="[used_quantity]"]');
        if (qtyInput) qtyInput.value = '1';

        item.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.name) {
                el.name = el.name.replace(/\[\d+\]/, '[' + detailCount + ']');
            }
        });

        container.append(item);
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
    // LOCATION → toggle driver / report / service vehicle
    // ═══════════════════════════════════════════════════════════════
    function toggleFields() {
        const yer           = document.querySelector('input[name="yer"]:checked');
        const driverField   = document.getElementById('surucuField');
        const reportFields  = document.getElementById('bildirilmeFields');
        const vehicleField  = document.getElementById('serviceVehicleField');
        const vehicleSelect = document.getElementById('service_vehicle_id');

        if (! yer) {
            if (driverField)  driverField.style.display  = 'none';
            if (reportFields) reportFields.style.display = 'none';
            if (vehicleField) vehicleField.style.display = 'none';

            if (vehicleSelect) {
                vehicleSelect.disabled = true;
                vehicleSelect.required = false;
            }

            return;
        }

        if (yer.value === 'garage') {
            if (driverField)  driverField.style.display  = 'none';
            if (reportFields) reportFields.style.display = 'none';
            if (vehicleField) vehicleField.style.display = 'none';

            if (vehicleSelect) {
                vehicleSelect.disabled = true;
                vehicleSelect.required = false;
                vehicleSelect.value    = '';
            }
        } else {
            if (driverField)  driverField.style.display  = 'block';
            if (reportFields) reportFields.style.display = 'block';
            if (vehicleField) vehicleField.style.display = 'block';

            if (vehicleSelect) {
                vehicleSelect.disabled = false;
                vehicleSelect.required = true;
            }
        }

        // ─── Re-lookup filled part codes when the source changes ───
        document.querySelectorAll('#detailsContainer input[name*="[code]"]').forEach(input => {
            if (input.value.trim()) {
                getPartByCode(input);
            }
        });

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
    document.addEventListener('DOMContentLoaded', function () {
        // ⚡ Page load → do NOT reset the location (so old('yer') survives).
        handleComplaintTypeChange(false);
        toggleFields();

        // Re-lookup prefilled parts so the stock display reflects the
        // correct source (warehouse vs vehicle) on page load.
        document.querySelectorAll('#detailsContainer input[name*="[code]"]').forEach(input => {
            if (input.value.trim()) {
                getPartByCode(input);
            }
        });
    });
</script>