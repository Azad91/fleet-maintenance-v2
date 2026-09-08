@extends('layouts.app')

@section('title', 'Yeni Kart')

@section('content')
<div class="complaint-create-page">
    <div class="complaint-create-page__heading">
        <div>
            <span class="fleet-eyebrow">TEXNİKİ QEYD</span>
            <h1>Yeni kart aç</h1>
            <p>Avtobus, yer və görülən iş məlumatlarını ardıcıl daxil edin.</p>
        </div>
        <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--secondary">
            <i class="fas fa-arrow-left"></i> Kartlara qayıt
        </a>
    </div>
    <div class="card complaint-create-card">
        <div class="card-header">
            <h4><i class="fas fa-screwdriver-wrench"></i> Kart məlumatları</h4>
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

<script>
    let motorOilServices = [];
    let defaultComplaintMarkup = '';
    let defaultDetailsMarkup = '';

    function getBusByRoute(route_number) {
        if (!route_number) {
            document.getElementById('dqn').value = '';
            document.getElementById('bus_id').value = '';
            document.getElementById('km').value = '';
            document.getElementById('motor_oil_km').innerHTML = '<option value="">Baxım növünü seçin...</option>';
            document.getElementById('service_km').value = '';
            return;
        }

        fetch('/get-bus-id-by-xett/' + encodeURIComponent(route_number))
            .then(response => response.json())
            .then(data => {
                document.getElementById('dqn').value = data.dqn || '';
                document.getElementById('bus_id').value = data.bus_id || '';

                if (data.bus_id) {
                    fetch('/get-bus-km-by-id/' + data.bus_id)
                        .then(response => response.json())
                        .then(kmData => {
                            document.getElementById('km').value = kmData.km || '';
                            loadMotorOilServices(data.bus_id);
                        });
                }
            });
    }

    function toggleServiceFields() {
        const isService = document.getElementById('tip_texniki').checked;
        document.getElementById('serviceFields').hidden = !isService;
        const road = document.getElementById('yer_yol');

        if (isService) {
            document.getElementById('yer_qaraj').checked = true;
            road.disabled = true;
            toggleFields();
            const busId = document.getElementById('bus_id').value;
            if (busId) loadMotorOilServices(busId);
        } else {
            road.disabled = false;
            document.getElementById('service_km').value = '';
            document.getElementById('motor_oil_km').innerHTML = '<option value="">Əvvəl avtobus seçin...</option>';
            document.getElementById('complaintsContainer').innerHTML = defaultComplaintMarkup;
            document.getElementById('detailsContainer').innerHTML = defaultDetailsMarkup;
        }
    }

    function loadMotorOilServices(busId) {
        fetch('/get-motor-oil-services/' + busId)
            .then(response => response.json())
            .then(services => {
                motorOilServices = services;
                const select = document.getElementById('motor_oil_km');
                select.innerHTML = '<option value="">Baxım növünü seçin...</option>';
                services.forEach((service, index) => select.add(new Option(Number(service.km).toLocaleString('az-AZ') + ' KM yağ dəyişməsi', index)));
                if (document.getElementById('tip_texniki').checked && services.length) {
                    select.value = '0';
                    onServiceSelectChange();
                }
            });
    }

    function onServiceSelectChange() {
        const service = motorOilServices[document.getElementById('motor_oil_km').value];
        if (!service) return;
        document.getElementById('service_km').value = service.km;
        const title = Number(service.km).toLocaleString('az-AZ') + ' KM yağ dəyişməsi';
        setServiceComplaint(title);
        setServiceDetails(service.details, title);
    }

    function setServiceComplaint(title) {
        document.getElementById('complaintsContainer').innerHTML =
            '<div class="complaint-item input-group mb-2"><span class="input-group-text complaint-number">1.</span>' +
            '<input class="form-control" name="complaints[]" value="' + title + '" readonly required></div>';
    }

    function setServiceDetails(details, title) {
        const container = document.getElementById('detailsContainer');
        const employeeOptions = document.querySelector('select[name*="[employee_id]"]').innerHTML;
        container.innerHTML = '';

        details.forEach((detail, index) => {
            const amount = Number(detail.quantity || detail.miqdar) * Number(detail.count || detail.say || 1);
            container.insertAdjacentHTML('beforeend',
                '<div class="detail-item border rounded p-3 mb-2"><div class="d-flex justify-content-between align-items-center mb-2"><strong class="small">Avtomatik əlavə olunan detal</strong><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDetail(this)"><i class="bi bi-trash"></i> Detalı sil</button></div><div class="row g-3">' +
                '<div class="col-md-2"><label class="form-label">Şikayət</label><input class="form-control" value="' + title + '" readonly><input type="hidden" name="details[' + index + '][complaint_index]" value="0"></div>' +
                '<div class="col-md-2"><label class="form-label">Detal kodu</label><input class="form-control" name="details[' + index + '][code]" value="' + (detail.code || detail.kodu) + '" readonly></div>' +
                '<div class="col-md-3"><label class="form-label">Detal adı</label><input class="form-control input-disabled" value="' + (detail.name || detail.adi) + '" readonly></div>' +
                '<div class="col-md-1"><label class="form-label">Miqdar</label><input type="number" class="form-control" name="details[' + index + '][used_quantity]" value="' + amount + '" min="1" required></div>' +
                '<div class="col-md-4"><label class="form-label">İşi görən işçi</label><select class="form-select" name="details[' + index + '][employee_id]" required>' + employeeOptions + '</select></div>' +
                '</div><div class="mt-2"><label class="form-label">Görülən iş</label><textarea class="form-control" name="details[' + index + '][notes]" rows="2" required>' + title + '</textarea></div></div>');
        });
    }

    function addComplaint() {
        const source = document.querySelector('#complaintsContainer select') || document.querySelector('#complaintsContainer input');
        if (!source) return;
        const item = source.closest('.complaint-item').cloneNode(true);
        if(item.querySelector('select')) item.querySelector('select').value = '';
        if(item.querySelector('input:not([readonly])')) item.querySelector('input:not([readonly])').value = '';
        item.querySelector('.complaint-number').textContent = (document.querySelectorAll('.complaint-item').length + 1) + '.';
        document.getElementById('complaintsContainer').append(item);
    }

    function removeComplaint(button) {
        const items = document.querySelectorAll('.complaint-item');
        if (items.length > 1) button.closest('.complaint-item').remove();
    }

    function addDetail() {
        const source = document.querySelector('.detail-item');
        if (!source) return;
        const item = source.cloneNode(true);

        item.querySelectorAll('input:not([type="hidden"])').forEach(i => i.value = '');
        item.querySelectorAll('textarea').forEach(t => t.value = '');

        const newIndex = document.querySelectorAll('.detail-item').length;
        item.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.name) {
                el.name = el.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            }
        });

        document.getElementById('detailsContainer').append(item);
    }

    function removeDetail(button) {
        const items = document.querySelectorAll('.detail-item');
        if (items.length > 1) button.closest('.detail-item').remove();
    }

    let driverLookupRequest = 0;

    function getDriverByCode(code) {
        const normalizedCode = code.trim().toUpperCase();
        const nameInput = document.getElementById('driver_name');
        const idInput = document.getElementById('driver_id');
        const help = document.getElementById('driverHelp');
        const codeInput = document.getElementById('driver_code') || document.getElementById('driver_kodu');

        idInput.value = '';
        nameInput.value = '';
        codeInput.classList.remove('is-valid', 'is-invalid');

        if (!normalizedCode) {
            help.textContent = 'Kod seçildikdə sürücünün adı avtomatik doldurulur.';
            help.className = 'form-text';
            return;
        }

        const requestId = ++driverLookupRequest;
        help.textContent = 'Sürücü axtarılır...';

        fetch('/get-driver-by-kod/' + encodeURIComponent(normalizedCode))
            .then(response => response.json())
            .then(data => {
                if (requestId !== driverLookupRequest) return;
                if (data.found) {
                    nameInput.value = data.driver_ad || data.driver_name;
                    idInput.value = data.driver_id;
                    codeInput.value = normalizedCode;
                    codeInput.classList.add('is-valid');
                    help.textContent = 'Sürücü tapıldı.';
                    help.className = 'form-text text-success';
                } else {
                    codeInput.classList.add('is-invalid');
                    help.textContent = 'Bu kodla aktiv sürücü tapılmadı.';
                    help.className = 'form-text text-danger';
                }
            });
    }

    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked');
        if (!yer) return;

        const driverField = document.getElementById('surucuField');
        const reportFields = document.getElementById('bildirilmeFields');

        if (yer.value === 'qaraj') {
            if(driverField) driverField.style.display = 'none';
            if(reportFields) reportFields.style.display = 'none';
        } else {
            if(driverField) driverField.style.display = 'block';
            if(reportFields) reportFields.style.display = 'block';
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
                nameInput.value = data.detal_adi || data.name || '';
                stockInput.value = data.depo_miqdari || data.quantity || '';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        defaultComplaintMarkup = document.getElementById('complaintsContainer') ? document.getElementById('complaintsContainer').innerHTML : '';
        defaultDetailsMarkup = document.getElementById('detailsContainer') ? document.getElementById('detailsContainer').innerHTML : '';

        toggleFields();
        toggleServiceFields();

        const driverCodeInput = document.getElementById('driver_code') || document.getElementById('driver_kodu');
        if (driverCodeInput && driverCodeInput.value) getDriverByCode(driverCodeInput.value);
    });
</script>
@endsection
