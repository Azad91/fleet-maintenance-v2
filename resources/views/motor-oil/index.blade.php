@extends('layouts.app')

@section('title', __('messages.motor_oil.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🛢️ {{ __('messages.motor_oil.title') }}</h1>
    <div class="d-flex gap-2">
        @can('delete', App\Models\MotorOilDetail::class)
            <button type="button" class="btn btn-outline-danger" id="bulkDeleteAllBtn"
                    data-total="{{ $grouped->sum(fn ($items) => $items->count()) }}"
                    {{ $grouped->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-trash-fill"></i>
                {{ __('messages.common.bulk_delete_all') }}
                ({{ $grouped->sum(fn ($items) => $items->count()) }})
            </button>
        @endcan
        <a href="{{ route('motor-oil.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> {{ __('messages.motor_oil.import') }}
        </a>
    </div>
</div>

{{-- Hidden form for "delete all matching filter" --}}
<form id="bulkDeleteAllForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="search" id="bulkDeleteAllSearch" value="{{ request('search') }}">
</form>

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
            const newTotal = counter ? (counter.dataset.count || '0') : '0';

            if (totalEl) {
                totalEl.textContent = newTotal;
            }

            syncBulkDeleteAllButton(newTotal);
        })
        .catch(error => {
            console.error('Motor oil search error:', error);
        });
    }

    function syncBulkDeleteAllButton(total) {
        const btn = document.getElementById('bulkDeleteAllBtn');
        if (! btn) return;

        btn.dataset.total = total;
        btn.disabled = total === '0';
        btn.innerHTML =
            '<i class="bi bi-trash-fill"></i> '
            + @json(__('messages.common.bulk_delete_all'))
            + ' (' + total + ')';
    }

    // ════════════════════════════════════════════════════════════════
    // DELETE ALL MATCHING FILTER
    // ════════════════════════════════════════════════════════════════

    (function () {
        const btn       = document.getElementById('bulkDeleteAllBtn');
        const form      = document.getElementById('bulkDeleteAllForm');
        const searchEl  = document.getElementById('bulkDeleteAllSearch');

        if (! btn || ! form || ! searchEl) return;

        btn.addEventListener('click', () => {
            const total = parseInt(btn.dataset.total, 10) || 0;

            if (total === 0) return;

            const message = @json(__('messages.common.bulk_delete_all_confirm'))
                .replace(':count', total);

            if (! confirm(message)) return;

            const currentSearch = document.getElementById('motorOilSearchInput')?.value ?? '';
            const normalized = String(currentSearch).replace(/[.,\s]/g, '');

            searchEl.value = normalized;
            form.action = "{{ route('motor-oil.bulk.delete-all') }}";
            form.submit();
        });
    })();

    document.addEventListener('DOMContentLoaded', function () {
        const counter = document.querySelector('#motorOilResults .total-count');
        const totalEl = document.getElementById('totalCount');
        if (counter && totalEl) {
            totalEl.textContent = counter.dataset.count || '0';
        }
    });
</script>
@endsection
