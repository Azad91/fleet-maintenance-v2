<div class="row">
    <!-- Status & Type -->
    <div class="col-md-12 mb-3">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-bold">🏷️ Şikayət Növü</label>
                <div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="qezali" {{ ($complaint->complaint_type ?? '') == 'qezali' ? 'checked' : '' }}>
                        <label class="form-check-label">🚗 Qəzalı</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="nasazliq" {{ ($complaint->complaint_type ?? '') == 'nasazliq' ? 'checked' : '' }}>
                        <label class="form-check-label">⚠️ Nasazlıq</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                        <input class="form-check-input" type="radio" name="complaint_type" value="texniki_xidmet" {{ ($complaint->complaint_type ?? '') == 'texniki_xidmet' ? 'checked' : '' }}>
                        <label class="form-check-label">🔧 Texniki Xidmət</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bus -->
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">🚌 Avtobus</label>
        <div class="row">
            <div class="col-md-6">
                <label>Route No (və ya DQN)</label>
                <!-- ✅ DÜZƏLİŞ: $complaint->bus?->route_number (null-safe) -->
                <input type="text" class="form-control" id="route_number" placeholder="Xətt nömrəsi və ya DQN yazın..."
                       value="{{ $complaint->bus?->route_number ?? '' }}"
                       oninput="getBusByRoute(this.value)"
                       {{ isset($complaint->id) ? 'readonly style=background:#e9ecef;' : '' }}>
            </div>
            <div class="col-md-6">
                <label>DQN</label>
                <!-- ✅ DÜZƏLİŞ: $complaint->bus?->dqn (null-safe) -->
                <input type="text" class="form-control" id="dqn" value="{{ $complaint->bus?->dqn ?? '' }}" readonly style="background:#e9ecef;">
            </div>
        </div>
        <input type="hidden" name="bus_id" id="bus_id" value="{{ $complaint->bus_id ?? '' }}">
    </div>

    <!-- Location -->
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">📍 Yer</label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="yer" id="yer_yol" value="yol" {{ ($complaint->yer ?? '') == 'yol' ? 'checked' : '' }} onchange="toggleFields()">
                <label class="form-check-label" for="yer_yol">🛣️ Yol</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="yer" id="yer_qaraj" value="qaraj" {{ ($complaint->yer ?? 'qaraj') == 'qaraj' ? 'checked' : '' }} onchange="toggleFields()">
                <label class="form-check-label" for="yer_qaraj">🏠 Qaraj</label>
            </div>
        </div>
    </div>

    <!-- Driver -->
    <div class="col-md-12 mb-3" id="surucuField">
        <label class="form-label fw-bold">🧑‍✈️ Sürücü</label>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="driver_code" class="form-label">Sürücü Kodu</label>
                <input type="text" class="form-control" id="driver_code" name="driver_code"
                    placeholder="Məs. D-001" list="driverList"
                    oninput="getDriverByCode(this.value)"
                    value="{{ old('driver_code', $complaint->driver?->code ?? '') }}">
                <datalist id="driverList">
                    @foreach($drivers ?? [] as $driver)
                        <option value="{{ $driver->code }}">
                    @endforeach
                </datalist>
                <div id="driverHelp" class="form-text">Kod seçildikdə ad avtomatik dolacaq.</div>
            </div>
            <div class="col-md-8">
                <label for="driver_name" class="form-label">Sürücü Adı</label>
                <input type="text" class="form-control input-disabled" id="driver_name" name="driver_name"
                    placeholder="Avtomatik dolacaq..." readonly
                    value="{{ old('driver_name', $complaint->driver_name ?? '') }}">
                <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', $complaint->driver_id ?? '') }}">
            </div>
        </div>
    </div>

    <!-- Complaints -->
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">📝 Şikayətlər</label>
        <div id="complaintsContainer">
            @php
                $shikayetler = isset($complaint) && $complaint->items ? $complaint->items->pluck('description')->toArray() : [];
            @endphp

            @if(count($shikayetler) > 0)
                @foreach($shikayetler as $index => $shikayet)
                    <div class="complaint-item mb-2">
                        <div class="input-group">
                            <span class="input-group-text complaint-number">{{ $index + 1 }}.</span>
                            <select class="form-select" name="complaints[]" required>
                                <option value="">Şikayət seçin...</option>
                                @foreach($complaintTypes as $type)
                                    <option value="{{ $type->name }}" {{ trim($shikayet) == $type->name ? 'selected' : '' }}>
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
                            <option value="">Şikayət seçin...</option>
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
            <i class="bi bi-plus-circle"></i> Şikayət Əlavə Et
        </button>
    </div>

    <!-- KM -->
    <div class="col-md-12 mb-3">
        <label for="km" class="form-label fw-bold">📊 KM (Yürüş)</label>
        <input type="number" class="form-control" id="km" name="km" value="{{ old('km', $complaint->km ?? '') }}" min="0" readonly style="background:#e9ecef;">
    </div>

    <!-- Reported -->
    <div class="col-md-12" id="bildirilmeFields">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">📅 Bildirilmə Tarixi</label>
                <input type="date" class="form-control" name="reported_date" value="{{ old('reported_date', isset($complaint->reported_date) ? \Carbon\Carbon::parse($complaint->reported_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">🕐 Bildirilmə Saatı</label>
                <input type="time" class="form-control" name="reported_time" value="{{ old('reported_time', $complaint->reported_time ?? '') }}">
            </div>
        </div>
    </div>

    <!-- Start / End -->
    <div class="col-md-12">
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">📅 Başlama Tarixi</label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', isset($complaint->start_date) ? \Carbon\Carbon::parse($complaint->start_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">🕐 Başlama Saatı</label>
                <input type="time" class="form-control" name="start_time" value="{{ old('start_time', $complaint->start_time ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">📅 Bitmə Tarixi</label>
                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', isset($complaint->end_date) ? \Carbon\Carbon::parse($complaint->end_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">🕐 Bitmə Saatı</label>
                <input type="time" class="form-control" name="end_time" value="{{ old('end_time', $complaint->end_time ?? '') }}">
            </div>
        </div>
    </div>

    <!-- Status & Type -->
    <div class="col-md-12 mb-3">
        <div class="row">
            <div class="col-md-6">
                <label for="status" class="form-label fw-bold">📊 Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="gözləmədə" {{ ($complaint->status ?? '') == 'gözləmədə' ? 'selected' : '' }}>⏳ Gözləmədə</option>
                    <option value="işdə" {{ ($complaint->status ?? '') == 'işdə' ? 'selected' : '' }}>🔨 İşdə</option>
                    <option value="həll olundu" {{ ($complaint->status ?? '') == 'həll olundu' ? 'selected' : '' }}>✅ Həll olundu</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">🏷️ Şikayət Növü</label>
                <select class="form-select" name="complaint_type">
                    <option value="">Seçin...</option>
                    <option value="qezali" {{ ($complaint->complaint_type ?? '') == 'qezali' ? 'selected' : '' }}>🚗 Qəzalı</option>
                    <option value="nasazliq" {{ ($complaint->complaint_type ?? '') == 'nasazliq' ? 'selected' : '' }}>⚠️ Nasazlıq</option>
                    <option value="texniki_xidmet" {{ ($complaint->complaint_type ?? '') == 'texniki_xidmet' ? 'selected' : '' }}>🔧 Texniki Xidmət</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Parts -->
    <div class="col-md-12 complaint-details-card p-3 mb-3">
        <h5 class="fw-bold mb-3">🔧 İstifade Olunan Detallar</h5>
        <div id="detailsContainer">
            @php $detallarData = $detallar ?? []; @endphp
            @if(count($detallarData) > 0)
                @foreach($detallarData as $index => $detal)
                    <div class="detail-item">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Şikayət İndeksi</label>
                                <select class="form-select" name="details[{{ $index }}][shikayet_index]">
                                    <option value="0">Şikayət 1</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Detal Kodu</label>
                                <input type="text" class="form-control" name="details[{{ $index }}][code]"
                                    value="{{ $detal['code'] ?? '' }}" oninput="getPartByCode(this)">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Detal Adı</label>
                                <input type="text" class="form-control input-disabled" name="details[{{ $index }}][name]"
                                    value="{{ $detal['name'] ?? '' }}" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label fw-bold">Anbar Qalığı</label>
                                <input type="text" class="form-control input-disabled" name="details[{{ $index }}][stock_quantity]"
                                    value="{{ $detal['stock_quantity'] ?? '' }}" readonly>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label fw-bold">Miqdar</label>
                                <input type="number" class="form-control" name="details[{{ $index }}][used_quantity]"
                                    value="{{ $detal['used_quantity'] ?? 1 }}" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">İşçi</label>
                                <select class="form-select" name="details[{{ $index }}][employee_id]" required>
                                    <option value="">Seçin...</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ old("details.$index.employee_id", $detal['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                                    <i class="bi bi-trash"></i> Sil
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <label class="form-label fw-bold">📝 Görülən İş</label>
                                <textarea class="form-control" name="details[{{ $index }}][notes]" rows="2">{{ $detal['notes'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <hr>
                    </div>
                @endforeach
            @else
                <div class="detail-item">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Şikayət</label>
                            <select class="form-select" name="details[0][shikayet_index]">
                                <option value="0">Şikayət 1</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Detal Kodu</label>
                            <input type="text" class="form-control" name="details[0][code]"
                                placeholder="Məs. D-001" oninput="getPartByCode(this)">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Detal Adı</label>
                            <input type="text" class="form-control input-disabled" name="details[0][name]" readonly>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold">Anbar Qalığı</label>
                            <input type="text" class="form-control input-disabled" name="details[0][stock_quantity]" readonly>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold">Miqdar</label>
                            <input type="number" class="form-control" name="details[0][used_quantity]"
                                placeholder="0" min="1" value="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">İşçi</label>
                            <select class="form-select" name="details[0][employee_id]" required>
                                <option value="">Seçin...</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetail(this)">
                                <i class="bi bi-trash"></i> Sil
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <label class="form-label fw-bold">📝 Görülən İş (Qeyd)</label>
                            <textarea class="form-control" name="details[0][notes]" rows="2" placeholder="Edilən iş barədə qeyd..."></textarea>
                        </div>
                    </div>
                    <hr>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addDetail()">
            <i class="bi bi-plus-circle"></i> Detal Əlavə Et
        </button>
    </div>

    <div class="col-md-12 d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Yadda Saxla
        </button>
        <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>
</div>
