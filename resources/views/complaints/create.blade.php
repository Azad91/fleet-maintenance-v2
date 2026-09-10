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
    function getBusByRoute(route_number) {
        if (!route_number) {
            document.getElementById('dqn').value = '';
            document.getElementById('bus_id').value = '';
            document.getElementById('km').value = '';
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
                        })
                        .catch(err => console.error('KM error:', err));
                }
            })
            .catch(err => console.error('Bus search error:', err));
    }

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

    function addDetail() {
        const container = document.getElementById('detailsContainer');
        const source = container.querySelector('.detail-item');
        if (!source) return;

        const item = source.cloneNode(true);
        item.querySelectorAll('input:not([type="hidden"])').forEach(i => i.value = '');
        item.querySelectorAll('textarea').forEach(t => t.value = '');
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
        }
    }

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

    function toggleFields() {
        const yer = document.querySelector('input[name="yer"]:checked');
        if (!yer) return;

        const driverField = document.getElementById('surucuField');
        const reportFields = document.getElementById('bildirilmeFields');

        if (yer.value === 'garage') {
            if (driverField) driverField.style.display = 'none';
            if (reportFields) reportFields.style.display = 'none';
        } else {
            if (driverField) driverField.style.display = 'block';
            if (reportFields) reportFields.style.display = 'block';
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
                nameInput.value = data.detallar_name || '';
                stockInput.value = data.stock_quantity || '';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();

        const routeInput = document.getElementById('route_number');
        if (routeInput && routeInput.value && !document.getElementById('bus_id').value) {
            getBusByRoute(routeInput.value);
        }
    });
</script>
@endsection