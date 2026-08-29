<!-- ==================== 1. ŞİKAYƏT TİPİ ==================== -->
<div class="mb-3">
    <label class="form-label fw-bold">🏷️ Şikayət Tipi <span class="text-danger">*</span></label>
    <div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="sikayet_tipi" id="tip_qezali" value="qezali"
                   {{ old('sikayet_tipi') == 'qezali' ? 'checked' : '' }} onchange="toggleServiceFields()" required>
            <label class="form-check-label" for="tip_qezali">🚗 Qəzalı</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="sikayet_tipi" id="tip_nasazliq" value="nasazliq"
                   {{ old('sikayet_tipi') == 'nasazliq' ? 'checked' : '' }} onchange="toggleServiceFields()">
            <label class="form-check-label" for="tip_nasazliq">⚠️ Nasazlıq</label>
        </div>
        <div class="form-check form-check-inline me-3">
            <input class="form-check-input" type="radio" name="sikayet_tipi" id="tip_texniki" value="texniki_xidmet"
                   {{ old('sikayet_tipi') == 'texniki_xidmet' ? 'checked' : '' }} onchange="toggleServiceFields()">
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
                    <option value="{{ $bus->xett_no }}">
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
                    <option value="{{ $driver->kodu }}">
                @endforeach
            </datalist>
        </div>
        <div class="col-md-8">
            <label for="surucu_adi" class="form-label">Sürücü Adı</label>
            <input type="text" class="form-control input-disabled" id="surucu_adi" name="surucu_adi"
                   placeholder="Kod daxil edildikdə avtomatik gəlir..." readonly
                   value="{{ old('surucu_adi') }}">
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
                <label for="bildirilme_tarix" class="form-label fw-bold">📅 Bildirilme Tarix</label>
                <input type="date" class="form-control" id="bildirilme_tarix" name="bildirilme_tarix"
                       value="{{ old('bildirilme_tarix', date('Y-m-d')) }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="bildirilme_saat" class="form-label fw-bold">🕐 Bildirilme Saat</label>
                <input type="time" class="form-control" id="bildirilme_saat" name="bildirilme_saat"
                       value="{{ old('bildirilme_saat', now()->format('H:i')) }}">
            </div>
        </div>
    </div>
</div>

<!-- ==================== 8. İŞƏ BAŞLAMA ==================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="is_baslama_tarix" class="form-label fw-bold">📅 İşə Başlama Tarix <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="is_baslama_tarix" name="is_baslama_tarix" required value="{{ old('is_baslama_tarix', date('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="is_baslama_saat" class="form-label fw-bold">🕐 İşə Başlama Saat <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="is_baslama_saat" name="is_baslama_saat" required value="{{ old('is_baslama_saat', now()->format('H:i')) }}">
        </div>
    </div>
</div>

<!-- ==================== 9. İŞİN BİTMƏSİ ==================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="is_bitme_tarix" class="form-label fw-bold">📅 İşin Bitdiyi Tarix <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="is_bitme_tarix" name="is_bitme_tarix" required value="{{ old('is_bitme_tarix', date('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="is_bitme_saat" class="form-label fw-bold">🕐 İşin Bitdiyi Saat <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="is_bitme_saat" name="is_bitme_saat" required value="{{ old('is_bitme_saat', now()->format('H:i')) }}">
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
        <option value="həll olundu" {{ old('status') == 'həll olundu' ? 'selected' : '' }}>✅ Həll Olundu</option>
    </select>
</div>

<!-- ==================== 11. TEXNİKİ XİDMƏT ==================== -->
<div id="serviceFields" class="service-fields-hidden">
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

<!-- ==================== HIDDEN INPUT ==================== -->

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
                        <input type="text" class="form-control" name="detallar[0][kodu]" required
                               placeholder="Məs: D-001" oninput="getDetalByKod(this, 0)" value="{{ old('detallar.0.kodu') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Detal Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-disabled" name="detallar[0][adi]" required readonly disabled value="{{ old('detallar.0.adi') }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Depo Miqdarı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-disabled" name="detallar[0][depo_miqdari]" required readonly disabled value="{{ old('detallar.0.depo_miqdari') }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">İşlənən Miqdar <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="detallar[0][islenen_miqdar]" required
                               placeholder="0" min="0" value="{{ old('detallar.0.islenen_miqdar', 0) }}">
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
                        <textarea class="form-control" name="detallar[0][qeyd]" rows="2" required placeholder="Bu detal üçün görülən işlər...">{{ old('detallar.0.qeyd') }}</textarea>
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

<!-- ==================== 14. DÜYMƏLƏR ==================== -->
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-save"></i> Yadda Saxla
    </button>
    <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Geri
    </a>
</div>


@section('scripts')
<script>
    // ==================== 1. AVTOBUS SEÇİMİ (ƏSAS - POZULMAZ) ====================
    function getBusByXett(xett_no) {
        if (!xett_no) {
            document.getElementById('dqn').value = '';
            document.getElementById('bus_id').value = '';
            document.getElementById('km').value = '';
            document.getElementById('motor_oil_km').innerHTML = '<option value="">Baxım növünü seçin...</option>';
            document.getElementById('service_km').value = '';
            return;
        }

        fetch('{{ url('/get-bus-id-by-xett') }}/' + xett_no)
            .then(response => response.json())
            .then(data => {
                document.getElementById('dqn').value = data.dqn || '';
                document.getElementById('bus_id').value = data.bus_id || '';

                if (data.bus_id) {
                    fetch(`/get-bus-km-by-id/${data.bus_id}`)
                        .then(response => response.json())
                        .then(kmData => {
                            const km = kmData.km !== null && kmData.km !== undefined ? kmData.km : '';
                            document.getElementById('km').value = km;

                            // Əgər "Texniki Xidmət" seçilibsə, template-ləri yüklə
                            const selectedTip = document.querySelector('input[name="sikayet_tipi"]:checked');
                            if (selectedTip && selectedTip.value === 'texniki_xidmet') {
                                loadServiceTemplates(data.bus_id);
                            }
                        })
                        .catch(error => console.error('KM xətası:', error));
                }
            })
            .catch(error => console.error('Xəta:', error));
    }

    // ==================== 2. YOL / QARAJ ====================
    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked').value;
        const surucuField = document.getElementById('surucuField');
        const bildirilmeFields = document.getElementById('bildirilmeFields');
        const surucuInput = document.getElementById('surucu_adi');
        const bildirilmeTarix = document.getElementById('bildirilme_tarix');
        const bildirilmeSaat = document.getElementById('bildirilme_saat');

        if (yer === 'qaraj') {
            surucuField.style.display = 'none';
            bildirilmeFields.style.display = 'none';
            surucuInput.removeAttribute('required');
            bildirilmeTarix.removeAttribute('required');
            bildirilmeSaat.removeAttribute('required');
        } else {
            surucuField.style.display = 'block';
            bildirilmeFields.style.display = 'block';
            surucuInput.setAttribute('required', 'required');
            bildirilmeTarix.setAttribute('required', 'required');
            bildirilmeSaat.setAttribute('required', 'required');
        }

        // Yer dəyişəndə Baxım Növünü də yenilə
        toggleServiceFields();
    }

    // ==================== 3. TEXNİKİ XİDMƏT VƏ QARAJ ====================
function toggleServiceFields() {
    const selectedTip = document.querySelector('input[name="sikayet_tipi"]:checked');
    const serviceFields = document.getElementById('serviceFields');

    if (selectedTip && selectedTip.value === 'texniki_xidmet') {
        document.getElementById('yer_qaraj').checked = true;
        document.getElementById('surucuField').style.display = 'none';
        document.getElementById('bildirilmeFields').style.display = 'none';
        document.getElementById('driver_kodu').value = '';
        document.getElementById('surucu_adi').value = '';
        document.getElementById('driver_id').value = '';
        document.getElementById('bildirilme_tarix').removeAttribute('required');
        document.getElementById('bildirilme_saat').removeAttribute('required');
    }

    const selectedYer = document.querySelector('input[name="yer"]:checked');

    if ((!selectedTip || selectedTip.value !== 'texniki_xidmet') && selectedYer && selectedYer.value === 'yol') {
        document.getElementById('surucuField').style.display = 'block';
    }

    // Əgər həm Texniki Xidmət, həm də Qaraj seçilibsə
    if (selectedTip && selectedTip.value === 'texniki_xidmet' && selectedYer && selectedYer.value === 'qaraj') {
        // GÖSTƏR
        serviceFields.style.display = 'block';
        const busId = document.getElementById('bus_id').value;
        if (busId) {
            loadServiceTemplates(busId);
        }
    } else {
        // GİZLƏT
        serviceFields.style.display = 'none';
        document.getElementById('motor_oil_km').innerHTML = '<option value="">Baxım növünü seçin...</option>';
        document.getElementById('service_km').value = '';
    }
}

 // ==================== 4. BAXIM NÖVLƏRİNİ YÜKLƏ ====================
function loadServiceTemplates(busId) {
    if (!busId) {
        const select = document.getElementById('motor_oil_km');
        select.innerHTML = '<option value="">Avtobus seçin...</option>';
        return;
    }

    fetch(window.location.origin + '/get-motor-oil-services/' + busId)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('motor_oil_km');
            select.innerHTML = '<option value="">Baxım növünü seçin...</option>';

            data.forEach(service => {
                const option = document.createElement('option');
                option.value = service.km;
                option.textContent = `Yağ və texniki baxım — ${new Intl.NumberFormat('az').format(service.km)} KM`;
                option.dataset.km = service.km;
                option.dataset.details = JSON.stringify(service.details || []);
                select.appendChild(option);
            });

            if (select.options.length <= 1) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = '🔔 Növbəti baxım vaxtı hələ gəlməyib';
                option.disabled = true;
                option.selected = true;
                select.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Xəta:', error);
            const select = document.getElementById('motor_oil_km');
            select.innerHTML = '<option value="">Xəta baş verdi</option>';
        });
}

    // ==================== 5. DETALLARI TEMPLATE-DƏN DOLDUR ====================
    function fillDetallarFromTemplate(details) {
        const container = document.getElementById('detallarContainer');
        const firstItem = container.querySelector('.detallar-item');

        // Əgər artıq əlavə detallar varsa, onları təmizlə (yalnız 1-ci qalsın)
        while (container.children.length > 1) {
            container.removeChild(container.lastChild);
        }

        const firstInputs = firstItem.querySelectorAll('input');
        const firstSelect = firstItem.querySelector('select');

        if (firstSelect) {
            firstSelect.value = '0';
        }

        // Əgər details massivi boş deyilsə, 1-ci detalı doldur
        if (details.length > 0) {
            const firstDetail = details[0];
            firstInputs.forEach(input => {
                if (input.name.includes('[kodu]')) {
                    input.value = firstDetail.kodu || '';
                    if (firstDetail.kodu) {
                        getDetalByKod(input, 0);
                    }
                }
                if (input.name.includes('[adi]')) {
                    input.value = firstDetail.adi || '';
                }
                if (input.name.includes('[depo_miqdari]')) {
                    input.value = '';
                }
                if (input.name.includes('[islenen_miqdar]')) {
                    const miqdar = parseFloat(firstDetail.miqdar) || 0;
                    const say = parseInt(firstDetail.say) || 0;
                    input.value = miqdar * say;
                }
            });

            // Qalan detalları əlavə et (əgər varsa)
            for (let i = 1; i < details.length; i++) {
                addDetal();
                const items = container.querySelectorAll('.detallar-item');
                const newItem = items[i];
                const itemInputs = newItem.querySelectorAll('input');

                itemInputs.forEach(input => {
                    if (input.name.includes('[kodu]')) {
                        input.value = details[i].kodu || '';
                        if (details[i].kodu) {
                            getDetalByKod(input, i);
                        }
                    }
                    if (input.name.includes('[adi]')) {
                        input.value = details[i].adi || '';
                    }
                    if (input.name.includes('[depo_miqdari]')) {
                        input.value = '';
                    }
                    if (input.name.includes('[islenen_miqdar]')) {
                        const miqdar = parseFloat(details[i].miqdar) || 0;
                        const say = parseInt(details[i].say) || 0;
                        input.value = miqdar * say;
                    }
                });
            }
        }
    }

    // ==================== 6. BAXIM NÖVÜ SEÇİLƏNDƏ ====================
    function onServiceSelectChange() {
        const select = document.getElementById('motor_oil_km');
        const selectedOption = select.options[select.selectedIndex];

        if (!selectedOption || !selectedOption.value) return;

        const serviceKm = selectedOption.dataset.km || selectedOption.value;
        const templateName = selectedOption.textContent.trim();
        const details = JSON.parse(selectedOption.dataset.details || '[]');
        document.getElementById('service_km').value = serviceKm;

        // Şikayət siyahısına avtomatik əlavə et
        const shikayetSelects = document.querySelectorAll('select[name="shikayet[]"]');
        if (shikayetSelects.length > 0) {
            const firstShikayetSelect = shikayetSelects[0];
            let found = false;
            for (let i = 0; i < firstShikayetSelect.options.length; i++) {
                if (firstShikayetSelect.options[i].textContent.trim() === templateName.trim()) {
                    firstShikayetSelect.value = firstShikayetSelect.options[i].value;
                    found = true;
                    break;
                }
            }
            if (!found) {
                const newOption = document.createElement('option');
                newOption.value = templateName;
                newOption.textContent = templateName;
                newOption.selected = true;
                firstShikayetSelect.appendChild(newOption);
            }
        }

        // Detalları doldur
        if (details.length > 0) {
            const formattedDetails = details.map(d => ({
                kodu: d.kodu,
                adi: d.adi,
                depo_miqdari: d.miqdar,
                islenen_miqdar: parseFloat(d.miqdar) * parseInt(d.say || 1),
                shikayet_index: 0,
                qeyd: `${d.adi} - ${d.say || 1} dəfə`
            }));
            fillDetallarFromTemplate(formattedDetails);
        }
    }

    // ==================== 7. DİNAMİK ŞİKAYƏTLƏR ====================
    function addShikayet() {
        const container = document.getElementById('shikayetContainer');
        const items = container.querySelectorAll('.shikayet-item');
        const newNumber = items.length + 1;

        const newItem = document.createElement('div');
        newItem.className = 'shikayet-item input-group mb-2';
        newItem.innerHTML = `
            <span class="input-group-text shikayet-number">${newNumber}.</span>
            <select class="form-select" name="shikayet[]" required>
                <option value="">Şikayət seçin...</option>
                @foreach($complaintTypes as $type)
                    <option value="{{ $type->name }}">{{ $type->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-danger" onclick="removeShikayet(this)">
                <i class="bi bi-trash"></i>
            </button>
        `;
        container.appendChild(newItem);
        updateDetalOptions();
    }

    function removeShikayet(button) {
        const item = button.closest('.shikayet-item');
        if (document.querySelectorAll('.shikayet-item').length > 1) {
            item.remove();
            updateNumbers();
            updateDetalOptions();
        } else {
            alert('Ən azı bir şikayət olmalıdır!');
        }
    }

    function updateNumbers() {
        const items = document.querySelectorAll('.shikayet-item');
        items.forEach((item, index) => {
            const numberSpan = item.querySelector('.input-group-text');
            if (numberSpan) {
                numberSpan.textContent = (index + 1) + '.';
            }
        });
    }

    // ==================== 8. DİNAMİK DETALLAR ====================
    let detalCount = 1;

    function addDetal() {
        const container = document.getElementById('detallarContainer');

        const shikayetSelects = document.querySelectorAll('select[name="shikayet[]"]');
        let options = '';
        shikayetSelects.forEach((select, index) => {
            const selectedText = select.options[select.selectedIndex]?.text || `Şikayət ${index + 1}`;
            options += `<option value="${index}">${selectedText}</option>`;
        });

        if (!options) {
            options = `<option value="0">Şikayət 1</option>`;
        }

        const newItem = document.createElement('div');
        newItem.className = 'detallar-item';
        newItem.innerHTML = `
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Aid Olduğu Şikayət</label>
                        <select class="form-select" name="detallar[${detalCount}][shikayet_index]">
                            ${options}
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Detal Kodu</label>
                        <input type="text" class="form-control" name="detallar[${detalCount}][kodu]" required
                               placeholder="Məs: D-001" oninput="getDetalByKod(this, ${detalCount})">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Detal Adı</label>
                        <input type="text" class="form-control input-disabled" name="detallar[${detalCount}][adi]" required readonly disabled>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Depo Miqdarı</label>
                        <input type="text" class="form-control input-disabled" name="detallar[${detalCount}][depo_miqdari]" required readonly disabled>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">İşlənən Miqdar</label>
                        <input type="number" class="form-control" name="detallar[${detalCount}][islenen_miqdar]" required
                               placeholder="0" min="0" value="0">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label fw-bold">İşi görən işçi</label>
                        <select class="form-select" name="detallar[${detalCount}][employee_id]" required>
                            <option value="">İşçi seçin...</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name_with_position }}</option>
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
                        <label class="form-label fw-bold">📝 Görülən İşlər (Qeyd)</label>
                        <textarea class="form-control" name="detallar[${detalCount}][qeyd]" rows="2" required placeholder="Bu detal üçün görülən işlər..."></textarea>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newItem);
        detalCount++;
    }

    function removeDetal(button) {
        const item = button.closest('.detallar-item');
        if (document.querySelectorAll('.detallar-item').length > 1) {
            item.remove();
        } else {
            alert('Ən azı bir detal olmalıdır!');
        }
    }

    // ==================== 9. DETALLARIN SEÇİMLƏRİNİ YENİLƏ ====================
    function updateDetalOptions() {
        const shikayetSelects = document.querySelectorAll('select[name="shikayet[]"]');
        const detalSelects = document.querySelectorAll('select[name*="[shikayet_index]"]');

        detalSelects.forEach(select => {
            const currentValue = parseInt(select.value) || 0;
            select.innerHTML = '';

            shikayetSelects.forEach((shikayetSelect, index) => {
                const text = shikayetSelect.options[shikayetSelect.selectedIndex]?.text || `Şikayət ${index + 1}`;
                const option = document.createElement('option');
                option.value = index;
                option.textContent = text;
                if (index === currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            if (select.options.length === 0) {
                const option = document.createElement('option');
                option.value = 0;
                option.textContent = 'Şikayət 1';
                select.appendChild(option);
            }
        });
    }

    // ==================== 10. DETAL KODUNA GÖRƏ ANBAR - DAN MƏLUMAT ÇƏK ====================
    function getDetalByKod(input, index) {
        const kod = input.value;
        const item = input.closest('.detallar-item');
        const adiInput = item.querySelector('input[name*="[adi]"]');
        const depoInput = item.querySelector('input[name*="[depo_miqdari]"]');

        if (!kod) {
            adiInput.value = '';
            depoInput.value = '';
            return;
        }

        fetch(`/get-detal-by-kod/${kod}`)
            .then(response => response.json())
            .then(data => {
                adiInput.value = data.detal_adi || '';
                depoInput.value = data.depo_miqdari || '';
            })
            .catch(error => console.error('Xəta:', error));
    }

    // ==================== 11. SƏHİFƏ YÜKLƏNƏNDƏ ====================
    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();
    });

    // ==================== SÜRÜCÜ KODUNA GÖRƏ AD ÇƏK ====================
    function getDriverByKod(kod) {
        if (!kod) {
            document.getElementById('surucu_adi').value = '';
            document.getElementById('driver_id').value = '';
            return;
        }

        fetch(`/get-driver-by-kod/${kod}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('surucu_adi').value = data.driver_ad || '';
                document.getElementById('driver_id').value = data.driver_id || '';
            })
            .catch(error => console.error('Xəta:', error));
    }

</script>
@endsection
