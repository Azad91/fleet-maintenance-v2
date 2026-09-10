@extends('layouts.app')

@section('title', __('messages.motor_oil.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🛢️ {{ __('messages.motor_oil.title') }}</h1>
    <div>
        <a href="{{ route('motor-oil.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> {{ __('messages.motor_oil.import') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label for="motorOilSearchInput" class="form-label fw-bold">{{ __('messages.motor_oil.search_label') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="motorOilSearchInput" class="form-control"
                               placeholder="{{ __('messages.motor_oil.search_placeholder') }}"
                               value="{{ request('search') }}"
                               autocomplete="off"
                               oninput="liveSearch(this.value)">
                        <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                            <i class="bi bi-x-circle"></i> {{ __('messages.common.clear') }}
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">{{ __('messages.motor_oil.search_hint') }}</small>
                </div>
                <div class="col-md-4 text-md-end">
                    <small class="text-muted">
                        {{ __('messages.common.total') }}: <span id="totalCount">0</span> {{ __('messages.motor_oil.parts') }}
                    </small>
                </div>
            </div>
        </div>

        <div id="motorOilResults">
            @include('motor-oil.partials.table', ['grouped' => $grouped])
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let motorOilSearchTimeout = null;

    function liveSearch(query) {
        clearTimeout(motorOilSearchTimeout);
        motorOilSearchTimeout = setTimeout(() => {
            performMotorOilSearch(query);
        }, 300);
    }

    function clearSearch() {
        const input = document.getElementById('motorOilSearchInput');
        if (input) {
            input.value = '';
        }
        clearTimeout(motorOilSearchTimeout);
        performMotorOilSearch('');
    }

    function performMotorOilSearch(query) {
        const params = new URLSearchParams();
        const normalized = String(query ?? '').replace(/[.,\s]/g, '');

        if (normalized) {
            params.set('search', normalized);
        }

        const url = '{{ route('motor-oil.search') }}'
            + (params.toString() ? '?' + params.toString() : '');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Search failed: HTTP ' + response.status);
            }
            return response.text();
        })
        .then(html => {
            const container = document.getElementById('motorOilResults');
            if (!container) return;

            container.innerHTML = html;

            const counter = container.querySelector('.total-count');
            const totalEl = document.getElementById('totalCount');
            if (totalEl) {
                totalEl.textContent = counter ? (counter.dataset.count || '0') : '0';
            }
        })
        .catch(error => {
            console.error('Motor oil search error:', error);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const counter = document.querySelector('#motorOilResults .total-count');
        const totalEl = document.getElementById('totalCount');
        if (counter && totalEl) {
            totalEl.textContent = counter.dataset.count || '0';
        }
    });
</script>
@endsection