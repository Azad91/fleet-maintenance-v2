@extends('layouts.app')

@section('title', 'Kart Redaktə Et')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ Kart Redaktə Et</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('complaints.update', $complaint->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Avtobus -->
            <div class="mb-3">
                <label class="form-label fw-bold">🚌 Avtobus</label>
                <div class="row">
                    <div class="col-md-6">
                        <label>Xətt №</label>
                        <input type="text" class="form-control" value="{{ $complaint->bus->xett_no ?? '' }}" readonly style="background:#e9ecef;">
                    </div>
                    <div class="col-md-6">
                        <label>DQN</label>
                        <input type="text" class="form-control" value="{{ $complaint->bus->dqn ?? '' }}" readonly style="background:#e9ecef;">
                    </div>
                </div>
                <input type="hidden" name="bus_id" value="{{ $complaint->bus_id }}">
            </div>

            <!-- Yer -->
            <div class="mb-3">
                <label class="form-label fw-bold">📍 Yer</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="yer" id="yer_yol" value="yol" {{ $complaint->yer == 'yol' ? 'checked' : '' }} onchange="toggleFields()">
                        <label class="form-check-label" for="yer_yol">🛣️ Yol</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="yer" id="yer_qaraj" value="qaraj" {{ $complaint->yer == 'qaraj' ? 'checked' : '' }} onchange="toggleFields()">
                        <label class="form-check-label" for="yer_qaraj">🏠 Qaraj</label>
                    </div>
                </div>
            </div>

            <!-- Sürücü -->
            <div class="mb-3" id="surucuField">
                <label class="form-label fw-bold">🧑‍✈️ Sürücü</label>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="driver_kodu" class="form-label">Sürücü Kodu</label>
                        <input type="text" class="form-control" id="driver_kodu" name="driver_kodu"
                            placeholder="Məs: D-001" list="driverList"
                            oninput="getDriverByKod(this.value)"
                            value="{{ old('driver_kodu', $complaint->driver?->kodu ?? '') }}">
                        <datalist id="driverList">
                            @foreach($drivers ?? [] as $driver)
                                <option value="{{ $driver->kodu }}">
                            @endforeach
                        </datalist>
                        <div id="driverHelp" class="form-text">Kod seçildikdə sürücünün adı avtomatik doldurulur.</div>
                    </div>
                    <div class="col-md-8">
                        <label for="surucu_adi" class="form-label">Sürücü Adı</label>
                        <input type="text" class="form-control input-disabled" id="surucu_adi" name="surucu_adi"
                            placeholder="Kod daxil edildikdə avtomatik gəlir..." readonly
                            value="{{ old('surucu_adi', $complaint->surucu_adi ?? '') }}">
                        <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', $complaint->driver_id ?? '') }}">
                    </div>
                </div>
            </div>

            <!-- Şikayətlər -->
            <div class="mb-3">
                <label class="form-label fw-bold">📝 Şikayətlər</label>
                <div id="shikayetContainer">
                    @php
                        $shikayetler = explode("\n", $complaint->shikayet ?? '');
                        $shikayetler = array_filter($shikayetler);
                    @endphp

                    @if(count($shikayetler) > 0)
                        @foreach($shikayetler as $index => $shikayet)
                            <div class="shikayet-item mb-2">
                                <div class="input-group">
                                    <span class="input-group-text shikayet-number">{{ $index + 1 }}.</span>
                                    <select class="form-select" name="shikayet[]" required>
                                        <option value="">Şikayət seçin...</option>
                                        @foreach($complaintTypes as $type)
                                            <option value="{{ $type->name }}" {{ trim($shikayet) == $type->name ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-danger" onclick="removeShikayet(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="shikayet-item mb-2">
                            <div class="input-group">
                                <span class="input-group-text shikayet-number">1.</span>
                                <select class="form-select" name="shikayet[]" required>
                                    <option value="">Şikayət seçin...</option>
                                    @foreach($complaintTypes as $type)
                                        <option value="{{ $type->name }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-danger" onclick="removeShikayet(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addShikayet()">
                    <i class="bi bi-plus-circle"></i> Şikayət Əlavə Et
                </button>
            </div>

            <!-- KM -->
            <div class="mb-3">
                <label for="km" class="form-label fw-bold">📊 KM (Yürüş)</label>
                <input type="number" class="form-control" id="km" name="km" value="{{ old('km', $complaint->km) }}" min="0" readonly style="background:#e9ecef;">
            </div>

            <!-- Bildirilme -->
            <div id="bildirilmeFields">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">📅 Bildirilme Tarix</label>
                        <input type="date" class="form-control" name="bildirilme_tarix" value="{{ old('bildirilme_tarix', $complaint->bildirilme_tarix ? \Carbon\Carbon::parse($complaint->bildirilme_tarix)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">🕐 Bildirilme Saat</label>
                        <input type="time" class="form-control" name="bildirilme_saat" value="{{ old('bildirilme_saat', $complaint->bildirilme_saat) }}">
                    </div>
                </div>
            </div>

            <!-- İşə başlama -->
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">📅 İşə Başlama Tarix</label>
                    <input type="date" class="form-control" name="is_baslama_tarix" value="{{ old('is_baslama_tarix', $complaint->is_baslama_tarix ? \Carbon\Carbon::parse($complaint->is_baslama_tarix)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">🕐 İşə Başlama Saat</label>
                    <input type="time" class="form-control" name="is_baslama_saat" value="{{ old('is_baslama_saat', $complaint->is_baslama_saat) }}">
                </div>
            </div>

            <!-- İşin bitməsi -->
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">📅 İşin Bitdiyi Tarix</label>
                    <input type="date" class="form-control" name="is_bitme_tarix" value="{{ old('is_bitme_tarix', $complaint->is_bitme_tarix ? \Carbon\Carbon::parse($complaint->is_bitme_tarix)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">🕐 İşin Bitdiyi Saat</label>
                    <input type="time" class="form-control" name="is_bitme_saat" value="{{ old('is_bitme_saat', $complaint->is_bitme_saat) }}">
                </div>
            </div>

            <!-- Status -->
            <div class="mb-3">
                <label for="status" class="form-label fw-bold">📊 Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="gözləmədə" {{ $complaint->status == 'gözləmədə' ? 'selected' : '' }}>⏳ Gözləmədə</option>
                    <option value="işdə" {{ $complaint->status == 'işdə' ? 'selected' : '' }}>🔨 İşdə</option>
                    <option value="həll olundu" {{ $complaint->status == 'həll olundu' ? 'selected' : '' }}>✅ Həll Olundu</option>
                </select>
            </div>

            <!-- Şikayət Tipi -->
            <div class="mb-3">
                <label class="form-label fw-bold">🏷️ Şikayət Tipi</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sikayet_tipi" value="qezali" {{ $complaint->sikayet_tipi == 'qezali' ? 'checked' : '' }}>
                        <label class="form-check-label">🚗 Qəzalı</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sikayet_tipi" value="nasazliq" {{ $complaint->sikayet_tipi == 'nasazliq' ? 'checked' : '' }}>
                        <label class="form-check-label">⚠️ Nasazlıq</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sikayet_tipi" value="texniki_xidmet" {{ $complaint->sikayet_tipi == 'texniki_xidmet' ? 'checked' : '' }}>
                        <label class="form-check-label">🔧 Texniki Xidmət</label>
                    </div>
                </div>
            </div>

            <!-- Detallar -->
            <div class="complaint-details-card p-3 mb-3">
                <h5 class="fw-bold mb-3">🔧 İstifadə Olunan Detallar</h5>
                <div id="detallarContainer">
                    @php
                        $detallar = is_array($complaint->detallar) ? $complaint->detallar : json_decode($complaint->detallar, true);
                    @endphp

                    @if($detallar && count($detallar) > 0)
                        @foreach($detallar as $index => $detal)
                            <div class="detallar-item">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Aid Olduğu Şikayət</label>
                                        <select class="form-select" name="detallar[{{ $index }}][shikayet_index]">
                                            @php
                                                $shikayetler_list = explode("\n", $complaint->shikayet ?? '');
                                                $shikayetler_list = array_filter($shikayetler_list);
                                            @endphp
                                            @if(count($shikayetler_list) > 0)
                                                @foreach($shikayetler_list as $i => $s)
                                                    <option value="{{ $i }}" {{ ($detal['shikayet_index'] ?? 0) == $i ? 'selected' : '' }}>
                                                        {{ trim($s) }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="0">Şikayət 1</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Detal Kodu</label>
                                        <input type="text" class="form-control" name="detallar[{{ $index }}][kodu]"
                                            value="{{ $detal['kodu'] ?? '' }}" oninput="getDetalByKod(this, {{ $index }})">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Detal Adı</label>
                                        <input type="text" class="form-control input-disabled" name="detallar[{{ $index }}][adi]"
                                            value="{{ $detal['adi'] ?? '' }}" readonly disabled>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold">Depo Miqdarı</label>
                                        <input type="text" class="form-control input-disabled" name="detallar[{{ $index }}][depo_miqdari]"
                                            value="{{ $detal['depo_miqdari'] ?? '' }}" readonly disabled>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold">İşlənən Miqdar</label>
                                        <input type="number" class="form-control" name="detallar[{{ $index }}][islenen_miqdar]"
                                            value="{{ $detal['islenen_miqdar'] ?? 1 }}" min="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">İşi görən işçi</label>
                                        <select class="form-select" name="detallar[{{ $index }}][employee_id]" required>
                                            <option value="">İşçi seçin...</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}" {{ old("detallar.$index.employee_id", $detal['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name_with_position }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetal(this)">
                                            <i class="bi bi-trash"></i> Sil
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">📝 Görülən İşlər (Qeyd)</label>
                                        <textarea class="form-control" name="detallar[{{ $index }}][qeyd]" rows="2">{{ $detal['qeyd'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="detallar-item">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Aid Olduğu Şikayət</label>
                                    <select class="form-select" name="detallar[0][shikayet_index]">
                                        <option value="0">Şikayət 1</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Detal Kodu</label>
                                    <input type="text" class="form-control" name="detallar[0][kodu]"
                                        placeholder="Məs: D-001" oninput="getDetalByKod(this, 0)">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Detal Adı</label>
                                    <input type="text" class="form-control input-disabled" name="detallar[0][adi]" readonly disabled>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label fw-bold">Depo Miqdarı</label>
                                    <input type="text" class="form-control input-disabled" name="detallar[0][depo_miqdari]" readonly disabled>
                                </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-bold">İşlənən Miqdar</label>
                                        <input type="number" class="form-control" name="detallar[0][islenen_miqdar]"
                                        placeholder="0" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">İşi görən işçi</label>
                                        <select class="form-select" name="detallar[0][employee_id]" required>
                                            <option value="">İşçi seçin...</option>
                                            @foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->full_name_with_position }}</option>@endforeach
                                        </select>
                                    </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetal(this)">
                                        <i class="bi bi-trash"></i> Sil
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <label class="form-label fw-bold">📝 Görülən İşlər (Qeyd)</label>
                                    <textarea class="form-control" name="detallar[0][qeyd]" rows="2" placeholder="Bu detal üçün görülən işlər..."></textarea>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addDetal()">
                    <i class="bi bi-plus-circle"></i> Detal Əlavə Et
                </button>
                <small class="text-muted d-block mt-1">Hər detal hansı şikayətə aid olduğunu seçin.</small>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Yenilə
            </button>
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked');
        if (!yer) return;

        const surucuField = document.getElementById('surucuField');
        const bildirilmeFields = document.getElementById('bildirilmeFields');

        if (yer.value === 'qaraj') {
            surucuField.style.display = 'none';
            bildirilmeFields.style.display = 'none';
        } else {
            surucuField.style.display = 'block';
            bildirilmeFields.style.display = 'block';
        }
    }

    function addShikayet() {
        const container = document.getElementById('shikayetContainer');
        const items = container.querySelectorAll('.shikayet-item');
        const newNumber = items.length + 1;

        const newItem = document.createElement('div');
        newItem.className = 'shikayet-item mb-2';
        newItem.innerHTML = `
            <div class="input-group">
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
            </div>
        `;
        container.appendChild(newItem);
        updateDetalOptions();
    }

    function removeShikayet(button) {
        const item = button.closest('.shikayet-item');
        if (document.querySelectorAll('.shikayet-item').length > 1) {
            item.remove();
            updateDetalOptions();
        } else {
            alert('Ən azı bir şikayət olmalıdır!');
        }
    }

    let detalCount = {{ count($detallar ?? []) > 0 ? count($detallar) : 1 }};

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
                    <label class="form-label fw-bold">Aid Olduğu Şikayət</label>
                    <select class="form-select" name="detallar[${detalCount}][shikayet_index]">
                        ${options}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Detal Kodu</label>
                    <input type="text" class="form-control" name="detallar[${detalCount}][kodu]" placeholder="Məs: D-001" oninput="getDetalByKod(this, ${detalCount})">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Detal Adı</label>
                    <input type="text" class="form-control input-disabled" name="detallar[${detalCount}][adi]" readonly disabled>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Depo Miqdarı</label>
                    <input type="text" class="form-control input-disabled" name="detallar[${detalCount}][depo_miqdari]" readonly disabled>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">İşlənən Miqdar</label>
                    <input type="number" class="form-control" name="detallar[${detalCount}][islenen_miqdar]" placeholder="0" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">İşi görən işçi</label>
                    <select class="form-select" name="detallar[${detalCount}][employee_id]" required>
                        <option value="">İşçi seçin...</option>
                        @foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->full_name_with_position }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDetal(this)">
                        <i class="bi bi-trash"></i> Sil
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <label class="form-label fw-bold">📝 Görülən İşlər (Qeyd)</label>
                    <textarea class="form-control" name="detallar[${detalCount}][qeyd]" rows="2" placeholder="Bu detal üçün görülən işlər..."></textarea>
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
        });
    }

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

    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();
    });
    let driverLookupRequest = 0;

    function getDriverByKod(kod) {
        const normalizedKod = kod.trim().toUpperCase();
        const nameInput = document.getElementById('surucu_adi');
        const idInput = document.getElementById('driver_id');
        const help = document.getElementById('driverHelp');
        const codeInput = document.getElementById('driver_kodu');

        idInput.value = '';
        nameInput.value = '';
        codeInput.classList.remove('is-valid', 'is-invalid');

        if (!normalizedKod) {
            help.textContent = 'Kod seçildikdə sürücünün adı avtomatik doldurulur.';
            help.className = 'form-text';
            return;
        }

        const requestId = ++driverLookupRequest;
        help.textContent = 'Sürücü axtarılır...';
        help.className = 'form-text';

        fetch('/get-driver-by-kod/' + encodeURIComponent(normalizedKod))
            .then(response => {
                if (!response.ok) throw new Error('Sürücü axtarışı alınmadı.');
                return response.json();
            })
            .then(data => {
                if (requestId !== driverLookupRequest) return;
                if (data.found) {
                    nameInput.value = data.driver_ad;
                    idInput.value = data.driver_id;
                    codeInput.value = normalizedKod;
                    codeInput.classList.add('is-valid');
                    help.textContent = 'Sürücü tapıldı.';
                    help.className = 'form-text text-success';
                } else {
                    codeInput.classList.add('is-invalid');
                    help.textContent = 'Bu kodla aktiv sürücü tapılmadı.';
                    help.className = 'form-text text-danger';
                }
            })
            .catch(() => {
                if (requestId !== driverLookupRequest) return;
                codeInput.classList.add('is-invalid');
                help.textContent = 'Sürücü məlumatı yüklənmədi. Yenidən cəhd edin.';
                help.className = 'form-text text-danger';
            });
    }
</script>
@endsection
