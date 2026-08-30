@extends('layouts.app')

@section('title', 'Yeni Kart')

@section('content')
<div class="complaint-create-page">
    <div class="complaint-create-page__heading">
        <div><span class="fleet-eyebrow">TEXNİKİ QEYD</span><h1>Yeni kart aç</h1><p>Avtobus, yer və görülən iş məlumatlarını ardıcıl daxil edin.</p></div>
        <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--secondary"><i class="fas fa-arrow-left"></i> Kartlara qayıt</a>
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

<!-- 🔥 JAVASCRIPT BİRBİRBİRBAŞA BURADA -->
<script>
    let motorOilServices = [];
    let defaultComplaintMarkup = '';
    let defaultDetailsMarkup = '';

    // ==================== AVTOBUS SEÇİMİ ====================
    function getBusByXett(xett_no) {
        console.log('🔍 Axtarılan xətt:', xett_no);

        if (!xett_no) {
            document.getElementById('dqn').value = '';
            document.getElementById('bus_id').value = '';
            document.getElementById('km').value = '';
            document.getElementById('motor_oil_km').innerHTML = '<option value="">Baxım növünü seçin...</option>';
            document.getElementById('service_km').value = '';
            return;
        }

        fetch('/get-bus-id-by-xett/' + encodeURIComponent(xett_no))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Şəbəkə xətası: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Bus məlumatları:', data);

                document.getElementById('dqn').value = data.dqn || '';
                document.getElementById('bus_id').value = data.bus_id || '';

                if (data.bus_id) {
                    fetch('/get-bus-km-by-id/' + data.bus_id)
                        .then(response => response.json())
                        .then(kmData => {
                            console.log('✅ KM məlumatı:', kmData);
                            document.getElementById('km').value = kmData.km || '';
                            loadMotorOilServices(data.bus_id);
                        })
                        .catch(error => console.error('❌ KM xətası:', error));
                }
            })
            .catch(error => console.error('❌ Bus axtarış xətası:', error));
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
            document.getElementById('shikayetContainer').innerHTML = defaultComplaintMarkup;
            document.getElementById('detallarContainer').innerHTML = defaultDetailsMarkup;
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
            })
            .catch(error => console.error('❌ Baxım paketi xətası:', error));
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
        document.getElementById('shikayetContainer').innerHTML =
            '<div class="shikayet-item input-group mb-2"><span class="input-group-text shikayet-number">1.</span>' +
            '<input class="form-control" name="shikayet[]" value="' + title + '" readonly required></div>';
    }

    function setServiceDetails(details, title) {
        const container = document.getElementById('detallarContainer');
        const employeeOptions = document.querySelector('select[name*="[employee_id]"]').innerHTML;
        container.innerHTML = '';

        details.forEach((detail, index) => {
            const amount = Number(detail.miqdar) * Number(detail.say || 1);
            container.insertAdjacentHTML('beforeend',
                '<div class="detallar-item border rounded p-3 mb-2"><div class="row g-3">' +
                '<div class="col-md-2"><label class="form-label">Şikayət</label><input class="form-control" value="' + title + '" readonly><input type="hidden" name="detallar[' + index + '][shikayet_index]" value="0"></div>' +
                '<div class="col-md-2"><label class="form-label">Detal kodu</label><input class="form-control" name="detallar[' + index + '][kodu]" value="' + detail.kodu + '" readonly></div>' +
                '<div class="col-md-3"><label class="form-label">Detal adı</label><input class="form-control input-disabled" value="' + detail.adi + '" readonly></div>' +
                '<div class="col-md-1"><label class="form-label">Miqdar</label><input type="number" class="form-control" name="detallar[' + index + '][islenen_miqdar]" value="' + amount + '" min="1" required></div>' +
                '<div class="col-md-4"><label class="form-label">İşi görən işçi</label><select class="form-select" name="detallar[' + index + '][employee_id]" required>' + employeeOptions + '</select></div>' +
                '</div><div class="mt-2"><label class="form-label">Görülən iş</label><textarea class="form-control" name="detallar[' + index + '][qeyd]" rows="2" required>' + title + '</textarea></div></div>');
        });
    }

    function addShikayet() {
        const source = document.querySelector('#shikayetContainer select');
        if (!source) return;
        const item = source.closest('.shikayet-item').cloneNode(true);
        item.querySelector('select').value = '';
        item.querySelector('.shikayet-number').textContent = (document.querySelectorAll('.shikayet-item').length + 1) + '.';
        document.getElementById('shikayetContainer').append(item);
    }

    function removeShikayet(button) {
        const items = document.querySelectorAll('.shikayet-item');
        if (items.length > 1) button.closest('.shikayet-item').remove();
    }

    function addDetal() {
        const source = document.querySelector('.detallar-item');
        if (!source) return;
        const item = source.cloneNode(true);
        document.getElementById('detallarContainer').append(item);
    }

    function removeDetal(button) {
        const items = document.querySelectorAll('.detallar-item');
        if (items.length > 1) button.closest('.detallar-item').remove();
    }

    // ==================== YOL / QARAJ ====================
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

    // ==================== DETAL KODUNA GÖRƏ ANBAR ====================
    function getDetalByKod(input) {
        const kod = input.value;
        const item = input.closest('.detallar-item');
        const adiInput = item.querySelector('input[name*="[adi]"]');
        const depoInput = item.querySelector('input[name*="[depo_miqdari]"]');

        if (!kod) {
            adiInput.value = '';
            depoInput.value = '';
            return;
        }

        fetch('/get-detal-by-kod/' + encodeURIComponent(kod))
            .then(response => response.json())
            .then(data => {
                adiInput.value = data.detal_adi || '';
                depoInput.value = data.depo_miqdari || '';
            })
            .catch(error => console.error('❌ Detal xətası:', error));
    }

    // ==================== SƏHİFƏ YÜKLƏNƏNDƏ ====================
    document.addEventListener('DOMContentLoaded', function() {
        defaultComplaintMarkup = document.getElementById('shikayetContainer').innerHTML;
        defaultDetailsMarkup = document.getElementById('detallarContainer').innerHTML;
        toggleFields();
        toggleServiceFields();
    });
</script>
@endsection
