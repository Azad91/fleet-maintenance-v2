@extends('layouts.app')

@section('title', 'Motor Oil')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🛢️ Motor Oil</h1>
    <div>
        <a href="{{ route('motor-oil.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Import from Excel
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        {{-- Search --}}
        <div class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label for="motorOilSearchInput" class="form-label fw-bold">Search by KM</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text"
                               id="motorOilSearchInput"
                               class="form-control"
                               placeholder="🔍 Enter KM (e.g. 36000)..."
                               value="{{ request('search') }}"
                               autocomplete="off"
                               oninput="liveSearch(this.value)">
                        <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        Only KM value is searched. Letters and symbols are ignored.
                    </small>
                </div>
                <div class="col-md-4 text-md-end">
                    <small class="text-muted">
                        Total: <span id="totalCount">0</span> parts
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

    /**
     * Debounced live search — user stops typing for 300ms before request fires.
     */
    function liveSearch(query) {
        clearTimeout(motorOilSearchTimeout);
        motorOilSearchTimeout = setTimeout(() => {
            performMotorOilSearch(query);
        }, 300);
    }

    /**
     * Clear input and reset results.
     */
    function clearSearch() {
        const input = document.getElementById('motorOilSearchInput');
        if (input) {
            input.value = '';
        }
        clearTimeout(motorOilSearchTimeout);
        performMotorOilSearch('');
    }

    /**
     * Actual AJAX search against the motor-oil.search route.
     */
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

    // Update counter on initial page load.
    document.addEventListener('DOMContentLoaded', function () {
        const counter = document.querySelector('#motorOilResults .total-count');
        const totalEl = document.getElementById('totalCount');
        if (counter && totalEl) {
            totalEl.textContent = counter.dataset.count || '0';
        }
    });
</script>
@endsection
