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
    function getBusByDqn(dqnValue) {
        const input = document.getElementById('dqn');
        const routeInput = document.getElementById('route_number');
        const busIdInput = document.getElementById('bus_id');
        const kmInput = document.getElementById('km');
        const help = document.getElementById('dqnHelp');

        const dqn = (dqnValue || '').trim().toUpperCase();

        // Reset
        routeInput.value = '';
        busIdInput.value = '';
        kmInput.value = '';
        help.textContent = '';
        help.className = 'form-text';
        input.classList.remove('is-valid', 'is-invalid');

        if (!dqn) return;

        fetch('/get-bus-by-dqn/' + encodeURIComponent(dqn), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                routeInput.value = data.route_number || '';
                busIdInput.value = data.bus_id || '';
                kmInput.value = data.km || '';
                input.classList.add('is-valid');
                help.textContent = '';
                help.className = 'form-text';
            } else {
                input.classList.add('is-invalid');
                help.textContent = @json(__('messages.complaints.dqn_not_found'));
                help.className = 'form-text text-danger';
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
        item.querySelectorAll('input[type="hidden"]').forEach(i => i.value = '');
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

    let employeeLookupRequest = 0;

    function getEmployeeByCode(input) {
        const code = input.value.trim().toUpperCase();
        const item = input.closest('.detail-item');
        const nameInput = item.querySelector('input[name*="[employee_name]"]');
        const idInput = item.querySelector('input[name*="[employee_id]"]');

        nameInput.value = '';
        idInput.value = '';
        input.classList.remove('is-valid', 'is-invalid');

        if (!code) return;

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
                idInput.value = data.employee_id || '';
                input.classList.add('is-valid');
            } else {
                input.classList.add('is-invalid');
            }
        })
        .catch(error => console.error('Employee lookup error:', error));
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

        // Create page: DQN is empty, nothing to look up on load.
        // (Validation errors → old DQN is rendered by the server and the
        //  hidden bus_id is preserved; no extra lookup needed.)
    });
</script>
@endsection
