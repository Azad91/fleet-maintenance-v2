<!-- ==================== 1. ŞİKAYƏT TİPİ ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">🏷️ Şikayət Tipi <span class="text-danger">*</span></label>
    <div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="complaint_type" id="tip_qezali" value="qezali"
                   {{ old('complaint_type') == 'qezali' ? 'checked' : '' }} onchange="toggleServiceFields()" required>
            <label class="form-check-label" for="tip_qezali">🚗 Qəzalı</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="complaint_type" id="tip_nasazliq" value="nasazliq"
                   {{ old('complaint_type') == 'nasazliq' ? 'checked' : '' }} onchange="toggleServiceFields()">
            <label class="form-check-label" for="tip_nasazliq">⚠️ Nasazlıq</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="complaint_type" id="tip_texniki" value="texniki_xidmet"
                   {{ old('complaint_type') == 'texniki_xidmet' ? 'checked' : '' }} onchange="toggleServiceFields()">
            <label class="form-check-label" for="tip_texniki">🔧 Texniki Xidmət</label>
        </div>
    </div>
</div>

<!-- ==================== 2. YOL / QARAJ ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">📍 Yer <span class="text-danger">*</span></label>
    <div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="yer" id="yer_yol" value="yol" {{ old('yer', 'yol') == 'yol' ? 'checked' : '' }} onchange="toggleFields()" required>
            <label class="form-check-label" for="yer_yol">🛣️ Yol</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="yer" id="yer_qaraj" value="qaraj" {{ old('yer') == 'qaraj' ? 'checked' : '' }} onchange="toggleFields()">
            <label class="form-check-label" for="yer_qaraj">🏠 Qaraj</label>
        </div>
    </div>
</div>

<!-- ==================== 3. AVTOBUS SEÇİMİ ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">🚌 Avtobus <span class="text-danger">*</span></label>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="xett_no" class="form-label">Xətt № <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="xett_no" name="xett_no" required
                   list="xettList" placeholder="Xətt nömrəsini yaz..."
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

<!-- ==================== 4. SÜRÜCÜ ==================== -->
<div class="mb-3" id="surucuField">
    <label class="form-label fw-bold">🧑‍✈️ Sürücü <span class="text-danger">*</span></label>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="driver_kodu" class="form-label">Sürücü Kodu</label>
            <input type="text" class="form-control" id="driver_kodu" name="driver_kodu"
                   placeholder="Məs: D-001" list="driverList"
                   oninput="getDriverByKod(this.value)" value="{{ old('driver_kodu') }}">
            <datalist id="driverList">
                @foreach($drivers ?? [] as $driver)
                    <option value="{{ $driver->code }}">
                @endforeach
            </datalist>
            <div id="driverHelp" class="form-text">Kod seçildikdə sürücünün adı avtomatik doldurulur.</div>
        </div>
        <div class="col-md-8">
            <label for="driver_name" class="form-label">Sürücü Adı</label>
            <input type="text" class="form-control input-disabled" id="driver_name" name="driver_name"
                   placeholder="Kod daxil edildikdə avtomatik gəlir..." readonly
                   value="{{ old('driver_name') }}">
            <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id') }}">
        </div>
    </div>
</div>

<!-- ==================== 5. DİNAMİK ŞİKAYƏTLƏR ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">📝 Şikayətlər <span class="text-danger">*</span></label>
    <div id="shikayetContainer">
        <div class="shikayet-item input-group mb-2">
            <span class="input-group-text shikayet-number">1.</span>
            <select class="form-select" name="shikayet[]" required>
                <option value="">Şikayət seçin...</option>
                @foreach($complaintTypes as $type)
                    <option value="{{ $type->name }}" {{ old('shikayet.0') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-danger" onclick="removeShikayet(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addShikayet()">
        <i class="bi bi-plus-circle"></i> Şikayət Əlavə Et
    </button>
    <small class="text-muted d-block mt-1">Hər şikayət ayrıca seçilir.</small>
</div>

<!-- ==================== 6. KM (Yürüş) ==================== -->
<div class="mb-3">
    <label for="km" class="form-label fw-bold">📊 KM (Yürüş) <span class="text-danger">*</span></label>
    <input type="number" class="form-control" id="km" name="km" required
           placeholder="Avtobus seçildikdə avtomatik dolur..." min="0" value="{{ old('km') }}">
    <small class="text-muted">Avtobus seçildikdə avtomatik olaraq dolur, istəsən dəyişə bilərsən.</small>
</div>

<!-- ==================== 7. BİLDİRİLMƏ ==================== -->
<div id="bildirilmeFields">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="reported_date" class="form-label fw-bold">📅 Bildirilme Tarix</label>
                <input type="date" class="form-control" id="reported_date" name="reported_date"
                       value="{{ old('reported_date', date('Y-m-d')) }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="reported_time" class="form-label fw-bold">🕐 Bildirilme Saat</label>
                <input type="time" class="form-control" id="reported_time" name="reported_time"
                       value="{{ old('reported_time', now()->format('H:i')) }}">
            </div>
        </div>
    </div>
</div>

<!-- ==================== 8. İŞƏ BAŞLAMA ==================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="start_date" class="form-label fw-bold">📅 İşə Başlama Tarix <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="start_date" name="start_date" required value="{{ old('start_date', date('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="start_time" class="form-label fw-bold">🕐 İşə Başlama Saat <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="start_time" name="start_time" required value="{{ old('start_time', now()->format('H:i')) }}">
        </div>
    </div>
</div>

<!-- ==================== 9. İŞİN BİTMƏSİ ==================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="end_date" class="form-label fw-bold">📅 İşin Bitdiyi Tarix <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="end_date" name="end_date" required value="{{ old('end_date', date('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="end_time" class="form-label fw-bold">🕐 İşin Bitdiyi Saat <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="end_time" name="end_time" required value="{{ old('end_time', now()->format('H:i')) }}">
        </div>
    </div>
</div>

<!-- ==================== 10. STATUS ==================== -->
<div class="mb-3">
    <label for="status" class="form-label fw-bold">📊 Status <span class="text-danger">*</span></label>
    <select class="form-select" id="status" name="status" required>
        <option value="">Status seçin...</option>
        <option value="gözləmədə" {{ old('status') == 'gözləmədə' ? 'selected' : '' }}>⏳ Gözləmədə</option>
        <option value="işdə" {{ old('status') == 'işdə' ? 'selected' : '' }}>🔨 İşdə</option>
    </select>
</div>

<!-- ==================== 11. TEXNİKİ XİDMƏT ==================== -->
<div id="serviceFields" class="service-fields-hidden" hidden>
    <div class="mb-3">
        <label for="motor_oil_km" class="form-label fw-bold">🔧 Baxım növü</label>
        <select class="form-select" id="motor_oil_km" onchange="onServiceSelectChange()">
            <option value="">Əvvəl avtobus seçin...</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="service_km" class="form-label fw-bold">📊 Planlanan baxım KM-i</label>
        <input type="number" class="form-control input-disabled" id="service_km" name="service_km" readonly min="0">
        <small class="text-muted">Seçilən baxımın KM-i Motor Yağı cədvəlindən avtomatik gəlir.</small>
    </div>
</div>

<!-- ==================== 12. DETALLAR ==================== -->
<div class="complaint-details-card p-3 mb-3">
    <h5 class="fw-bold mb-3">🔧 İstifadə Olunan Detallar <span class="text-danger">*</span></h5>
    <div id="detallarContainer">
        <div class="detallar-item">
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Aid Olduğu Şikayət <span class="text-danger">*</span></label>
                        <select class="form-select" name="detallar[0][shikayet_index]" required>
                            <option value="0">Şikayət 1</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Detal Kodu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="detallar[0][code]" required
                               placeholder="Məs: D-001" oninput="getDetalByKod(this, 0)" value="{{ old('detallar.0.code') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Detal Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-disabled" name="detallar[0][name]" required readonly disabled value="{{ old('detallar.0.name') }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Depo Miqdarı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-disabled" name="detallar[0][stock_quantity]" required readonly disabled value="{{ old('detallar.0.stock_quantity') }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">İşlənən Miqdar <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="detallar[0][used_quantity]" required
                               placeholder="0" min="1" value="{{ old('detallar.0.used_quantity', 1) }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">İşi görən işçi <span class="text-danger">*</span></label>
                        <select class="form-select" name="detallar[0][employee_id]" required>
                            <option value="">İşçi seçin...</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('detallar.0.employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name_with_position }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetal(this)">
                            <i class="bi bi-trash"></i> Sil
                        </button>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <div class="mb-2">
                        <label class="form-label fw-bold">📝 Görülən İşlər (Qeyd) <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="detallar[0][notes]" rows="2" required placeholder="Bu detal üçün görülən işlər...">{{ old('detallar.0.notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addDetal()">
        <i class="bi bi-plus-circle"></i> Detal Əlavə Et
    </button>
    <small class="text-muted d-block mt-1">Hər detal hansı şikayətə aid olduğunu seçin.</small>
</div>

<!-- ==================== 13. DÜYMƏLƏR ==================== -->
<div class="d-flex gap-2">
    @can('create', App\Models\Complaint::class)
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Yadda Saxla
        </button>
    @endcan
    <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Geri
    </a>
</div>
