<!-- ==================== 1. COMPLAINT TYPE ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">🏷️ Complaint Type <span class="text-danger">*</span></label>
    <div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="complaint_type" id="tip_qezali" value="qezali"
                   {{ old('complaint_type') == 'qezali' ? 'checked' : '' }} onchange="toggleServiceFields()" required>
            <label class="form-check-label" for="tip_qezali">🚗 Accident</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="complaint_type" id="tip_nasazliq" value="nasazliq"
                   {{ old('complaint_type') == 'nasazliq' ? 'checked' : '' }} onchange="toggleServiceFields()">
            <label class="form-check-label" for="tip_nasazliq">⚠️ Breakdown</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="complaint_type" id="tip_texniki" value="texniki_xidmet"
                   {{ old('complaint_type') == 'texniki_xidmet' ? 'checked' : '' }} onchange="toggleServiceFields()">
            <label class="form-check-label" for="tip_texniki">🔧 Maintenance</label>
        </div>
    </div>
</div>

<!-- ==================== 2. LOCATION ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">📍 Location <span class="text-danger">*</span></label>
    <div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="yer" id="yer_yol" value="yol" {{ old('yer', 'yol') == 'yol' ? 'checked' : '' }} onchange="toggleFields()" required>
            <label class="form-check-label" for="yer_yol">🛣️ Road</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="yer" id="yer_qaraj" value="qaraj" {{ old('yer') == 'qaraj' ? 'checked' : '' }} onchange="toggleFields()">
            <label class="form-check-label" for="yer_qaraj">🏠 Garage</label>
        </div>
    </div>
</div>

<!-- ==================== 3. BUS SELECTION ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">🚌 Bus <span class="text-danger">*</span></label>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="xett_no" class="form-label">Route No <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="xett_no" name="xett_no" required
                   list="xettList" placeholder="Enter route number..."
                   oninput="getBusByXett(this.value)" value="{{ old('xett_no') }}">
            <datalist id="xettList">
                @foreach($buses as $bus)
                    <option value="{{ $bus->route_number }}">
                @endforeach
            </datalist>
        </div>
        <div class="col-md-6">
            <label for="dqn" class="form-label">DQN <span class="text-danger">*</span></label>
            <input type="text" class="form-control input-disabled" id="dqn" name="dqn" readonly required value="{{ old('dqn') }}">
            <input type="hidden" name="bus_id" id="bus_id" value="{{ old('bus_id') }}">
        </div>
    </div>
</div>

<!-- ==================== 4. DRIVER ==================== -->
<div class="mb-3" id="surucuField">
    <label class="form-label fw-bold">🧑‍✈️ Driver <span class="text-danger">*</span></label>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="driver_kodu" class="form-label">Driver Code</label>
            <input type="text" class="form-control" id="driver_kodu" name="driver_kodu"
                   placeholder="e.g. D-001" list="driverList"
                   oninput="getDriverByKod(this.value)" value="{{ old('driver_kodu') }}">
            <datalist id="driverList">
                @foreach($drivers ?? [] as $driver)
                    <option value="{{ $driver->code }}">
                @endforeach
            </datalist>
            <div id="driverHelp" class="form-text">Driver name auto-fills when code is selected.</div>
        </div>
        <div class="col-md-8">
            <label for="driver_name" class="form-label">Driver Name</label>
            <input type="text" class="form-control input-disabled" id="driver_name" name="driver_name"
                   placeholder="Auto-filled..." readonly
                   value="{{ old('driver_name') }}">
            <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id') }}">
        </div>
    </div>
</div>

<!-- ==================== 5. DYNAMIC COMPLAINTS ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">📝 Complaints <span class="text-danger">*</span></label>
    <div id="complaintsContainer">
        <div class="complaint-item input-group mb-2">
            <span class="input-group-text shikayet-number">1.</span>
            <select class="form-select" name="complaints[]" required>
                <option value="">Select complaint...</option>
                @foreach($complaintTypes as $type)
                    <option value="{{ $type->name }}" {{ old('shikayet.0') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-danger" onclick="removeComplaint(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addComplaint()">
        <i class="bi bi-plus-circle"></i> Add Complaint
    </button>
    <small class="text-muted d-block mt-1">Each complaint is selected separately.</small>
</div>

<!-- ==================== 6. KM (Mileage) ==================== -->
<div class="mb-3">
    <label for="km" class="form-label fw-bold">📊 KM (Mileage) <span class="text-danger">*</span></label>
    <input type="number" class="form-control" id="km" name="km" required
           placeholder="Auto-filled when bus is selected..." min="0" value="{{ old('km') }}">
    <small class="text-muted">Auto-filled when bus is selected, you can change it if needed.</small>
</div>

<!-- ==================== 7. REPORTED ==================== -->
<div id="bildirilmeFields">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="reported_date" class="form-label fw-bold">📅 Reported Date</label>
                <input type="date" class="form-control" id="reported_date" name="reported_date"
                       value="{{ old('reported_date', date('Y-m-d')) }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="reported_time" class="form-label fw-bold">🕐 Reported Time</label>
                <input type="time" class="form-control" id="reported_time" name="reported_time"
                       value="{{ old('reported_time', now()->format('H:i')) }}">
            </div>
        </div>
    </div>
</div>

<!-- ==================== 8. START ==================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="start_date" class="form-label fw-bold">📅 Start Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="start_date" name="start_date" required value="{{ old('start_date', date('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="start_time" class="form-label fw-bold">🕐 Start Time <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="start_time" name="start_time" required value="{{ old('start_time', now()->format('H:i')) }}">
        </div>
    </div>
</div>

<!-- ==================== 9. END ==================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="end_date" class="form-label fw-bold">📅 End Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="end_date" name="end_date" required value="{{ old('end_date', date('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="end_time" class="form-label fw-bold">🕐 End Time <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="end_time" name="end_time" required value="{{ old('end_time', now()->format('H:i')) }}">
        </div>
    </div>
</div>

<!-- ==================== 10. STATUS ==================== -->
<div class="mb-3">
    <label for="status" class="form-label fw-bold">📊 Status <span class="text-danger">*</span></label>
    <select class="form-select" id="status" name="status" required>
        <option value="">Select status...</option>
        <option value="gözləmədə" {{ old('status') == 'gözləmədə' ? 'selected' : '' }}>⏳ Pending</option>
        <option value="işdə" {{ old('status') == 'işdə' ? 'selected' : '' }}>🔨 In Progress</option>
    </select>
</div>

<!-- ==================== 11. MAINTENANCE SERVICE ==================== -->
<div id="serviceFields" class="service-fields-hidden" hidden>
    <div class="mb-3">
        <label for="motor_oil_km" class="form-label fw-bold">🔧 Maintenance Type</label>
        <select class="form-select" id="motor_oil_km" onchange="onServiceSelectChange()">
            <option value="">Select bus first...</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="service_km" class="form-label fw-bold">📊 Planned Maintenance KM</label>
        <input type="number" class="form-control input-disabled" id="service_km" name="service_km" readonly min="0">
        <small class="text-muted">Auto-filled from Motor Oil table based on selected maintenance.</small>
    </div>
</div>

<!-- ==================== 12. PARTS ==================== -->
<div class="complaint-details-card p-3 mb-3">
    <h5 class="fw-bold mb-3">🔧 Used Parts <span class="text-danger">*</span></h5>
    <div id="detailsContainer">
        <div class="detail-item">
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Related Complaint <span class="text-danger">*</span></label>
                        <select class="form-select" name="details[0][shikayet_index]" required>
                            <option value="0">Complaint 1</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Part Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="detallar[0][code]" required
                               placeholder="e.g. D-001" oninput="getDetalByKod(this, 0)" value="{{ old('detallar.0.code') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Part Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-disabled" name="detallar[0][name]" required readonly disabled value="{{ old('detallar.0.name') }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Stock Qty <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-disabled" name="detallar[0][stock_quantity]" required readonly disabled value="{{ old('detallar.0.stock_quantity') }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Used Qty <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="detallar[0][used_quantity]" required
                               placeholder="0" min="1" value="{{ old('detallar.0.used_quantity', 1) }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Employee <span class="text-danger">*</span></label>
                        <select class="form-select" name="detallar[0][employee_id]" required>
                            <option value="">Select employee...</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('detallar.0.employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name_with_position }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <div class="mb-2">
                        <label class="form-label fw-bold">📝 Work Done (Notes) <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="detallar[0][notes]" rows="2" required placeholder="Work done for this part...">{{ old('detallar.0.notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addDetail()">
        <i class="bi bi-plus-circle"></i> Add Part
    </button>
    <small class="text-muted d-block mt-1">Each part must be linked to a complaint.</small>
</div>

<!-- ==================== 13. BUTTONS ==================== -->
<div class="d-flex gap-2">
    @can('create', App\Models\Complaint::class)
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Save
        </button>
    @endcan
    <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>