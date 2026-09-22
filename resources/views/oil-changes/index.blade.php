@extends('layouts.app')

@section('title', __('messages.oil_change.title'))

@php
    use App\Enums\OilType;
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-1">🛢️ {{ __('messages.oil_change.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.oil_change.subtitle') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('oil-changes.urgent') }}" class="btn btn-danger">
            <i class="fas fa-triangle-exclamation"></i> {{ __('messages.oil_change.urgent_title') }}
        </a>
        <a href="{{ route('oil-changes.import') }}" class="btn btn-success">
            <i class="fas fa-upload"></i> {{ __('messages.oil_change.import_title') }}
        </a>
        <a href="{{ route('oil-changes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('messages.oil_change.new') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{{ session('success') }}
    </div>
@endif

@if(session('warning'))
    <div class="fleet-alert fleet-alert--warning">
        <i class="fas fa-triangle-exclamation"></i>{{ session('warning') }}
    </div>
@endif

{{-- ─────────────────────────────────────────────────────────────── --}}
{{-- SEARCH FORM                                                    --}}
{{--                                                                --}}
{{-- Kept OUTSIDE #oilChangeResults so that AJAX replacement does   --}}
{{-- not steal focus from the active input. Type and status are     --}}
{{-- hidden fields updated by the tab/chip click handlers.          --}}
{{-- ─────────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body">
        <form id="oilChangeSearchForm" method="GET" action="{{ route('oil-changes.index') }}" autocomplete="off">
            <input type="hidden" name="type" id="oilTypeInput" value="{{ $activeType->value }}">
            <input type="hidden" name="status" id="oilStatusInput" value="{{ $statusFilter }}">

            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="searchDqn" class="form-label fw-bold">
                        <i class="fas fa-bus"></i> {{ __('messages.buses.dqn') }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text"
                               name="dqn"
                               id="searchDqn"
                               class="form-control"
                               value="{{ $filters['dqn'] }}"
                               placeholder="{{ __('messages.buses.filter_dqn') }}"
                               style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="searchRoute" class="form-label fw-bold">
                        <i class="fas fa-route"></i> {{ __('messages.buses.route_number') }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text"
                               name="route_number"
                               id="searchRoute"
                               class="form-control"
                               value="{{ $filters['route_number'] }}"
                               placeholder="{{ __('messages.buses.filter_route') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label for="searchKmMin" class="form-label fw-bold">
                        {{ __('messages.oil_change.search_km_min') }}
                    </label>
                    <input type="number"
                           name="km_min"
                           id="searchKmMin"
                           class="form-control"
                           value="{{ $filters['km_min'] }}"
                           min="0"
                           placeholder="0">
                </div>

                <div class="col-md-2">
                    <label for="searchKmMax" class="form-label fw-bold">
                        {{ __('messages.oil_change.search_km_max') }}
                    </label>
                    <input type="number"
                           name="km_max"
                           id="searchKmMax"
                           class="form-control"
                           value="{{ $filters['km_max'] }}"
                           min="0"
                           placeholder="999999">
                </div>

                <div class="col-md-2 d-flex gap-1 align-items-center">
                    <button type="button" class="btn btn-secondary flex-fill" id="oilClearFilters" title="{{ __('messages.common.reset') }}">
                        <i class="fas fa-xmark"></i>
                    </button>
                    <span class="text-muted small" id="oilSearchStatus" style="display: none;">
                        <i class="fas fa-arrow-rotate-right fa-spin"></i>
                    </span>
                </div>
            </div>

            <small class="text-muted d-block mt-2">{{ __('messages.oil_change.search_hint') }}</small>
        </form>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────────── --}}
{{-- RESULTS (chips + tabs + table) — replaced by AJAX              --}}
{{-- ─────────────────────────────────────────────────────────────── --}}
<div id="oilChangeResults">
    @include('oil-changes.partials.results', [
        'rows'         => $rows,
        'statusFilter' => $statusFilter,
        'activeType'   => $activeType,
        'typeCounts'   => $typeCounts,
        'urgentCount'  => $urgentCount,
        'filters'      => $filters,
    ])
</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    const form             = document.getElementById('oilChangeSearchForm');
    const results          = document.getElementById('oilChangeResults');
    const statusIndicator  = document.getElementById('oilSearchStatus');
    const typeInput        = document.getElementById('oilTypeInput');
    const statusInput      = document.getElementById('oilStatusInput');
    const clearBtn         = document.getElementById('oilClearFilters');

    if (! form || ! results) return;

    let timer          = null;
    let currentRequest = 0;

    // ═══════════════════════════════════════════════════════════════
    // SEARCH
    // ═══════════════════════════════════════════════════════════════

    function buildQuery() {
        const params = new URLSearchParams(new FormData(form));
        // Drop empty values so the URL stays clean.
        for (const [key, value] of Array.from(params.entries())) {
            if (value === '' || value === null) {
                params.delete(key);
            }
        }
        return params.toString();
    }

    function performSearch() {
        const qs = buildQuery();
        const requestId = ++currentRequest;

        statusIndicator.style.display = 'inline-block';

        const fetchUrl = "{{ route('oil-changes.search') }}" + (qs ? '?' + qs : '');

        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        })
        .then(response => {
            if (! response.ok) {
                throw new Error('Search failed: HTTP ' + response.status);
            }
            return response.text();
        })
        .then(html => {
            if (requestId !== currentRequest) return;

            results.innerHTML = html;

            // Sync the URL bar with the current filter state.
            const browserUrl = "{{ route('oil-changes.index') }}" + (qs ? '?' + qs : '');
            history.replaceState(null, '', browserUrl);

            // Re-bind tab and chip click handlers after the DOM swap.
            attachTabHandlers();
            attachChipHandlers();
        })
        .catch(error => console.error('Oil change search error:', error))
        .finally(() => {
            if (requestId === currentRequest) {
                statusIndicator.style.display = 'none';
            }
        });
    }

    function scheduleSearch(delay) {
        clearTimeout(timer);
        timer = setTimeout(performSearch, delay);
    }

    // ═══════════════════════════════════════════════════════════════
    // FILTER BINDINGS
    // ═══════════════════════════════════════════════════════════════

    form.addEventListener('input', function (e) {
        if (e.target.matches('input[name]')) {
            scheduleSearch(300);
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(timer);
        performSearch();
    });

    clearBtn?.addEventListener('click', function () {
        form.querySelectorAll('input[type="text"], input[type="number"]').forEach(el => {
            el.value = '';
        });
        clearTimeout(timer);
        performSearch();
    });

    // ═══════════════════════════════════════════════════════════════
    // TAB / CHIP HANDLERS
    //
    // Both keep their href for middle-click / JS-disabled fallback,
    // but the click is intercepted and routed through performSearch()
    // so the whole page does not reload.
    // ═══════════════════════════════════════════════════════════════

    function attachTabHandlers() {
        results.querySelectorAll('.oil-tab-link').forEach(link => {
            link.addEventListener('click', function (e) {
                // Allow middle-click / ctrl+click to open in new tab.
                if (e.metaKey || e.ctrlKey || e.button !== 0) return;

                e.preventDefault();
                typeInput.value = link.dataset.type;
                performSearch();
            });
        });
    }

    function attachChipHandlers() {
        results.querySelectorAll('.oil-status-chip').forEach(chip => {
            chip.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey || e.button !== 0) return;

                e.preventDefault();
                statusInput.value = chip.dataset.status;
                performSearch();
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════

    attachTabHandlers();
    attachChipHandlers();
})();
</script>
@endsection
