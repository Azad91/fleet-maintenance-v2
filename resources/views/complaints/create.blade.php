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
                        })
                        .catch(error => console.error('❌ KM xətası:', error));
                }
            })
            .catch(error => console.error('❌ Bus axtarış xətası:', error));
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
        toggleFields();
    });
</script>
@endsection
