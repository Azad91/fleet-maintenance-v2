@extends('layouts.app')

@section('title', __('messages.complaints.title'))

@php
    use App\Enums\ComplaintStatus;
    use App\Enums\ComplaintType;
    use App\Enums\Location;
@endphp

@section('content')
<div class="page-header">
    <h1>📋 {{ __('messages.complaints.title') }}</h1>
    <p class="text-muted">{{ __('messages.complaints.subtitle') }}</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2 flex-wrap">
        @can('create', App\Models\Complaint::class)
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> {{ __('messages.complaints.new') }}
            </a>
        @endcan
        @can('import', App\Models\Complaint::class)
            <a href="{{ route('complaints.import') }}" class="btn btn-success">
                <i class="bi bi-upload"></i> {{ __('messages.complaints.import') }}
            </a>
        @endcan
        <a href="{{ route('complaint-types.index') }}" class="btn btn-outline-info">
            <i class="bi bi-tags"></i> {{ __('messages.complaint_types.title') }}
        </a>
    </div>
    <span class="badge bg-primary rounded-pill" id="totalBadge">
        {{ __('messages.common.total') }}: <span id="totalCount">{{ $complaints->total() }}</span>
        {{ __('messages.complaints.total_label') }}
    </span>
</div>

{{-- ─── Filter panel ─── --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('complaints.index') }}" id="complaintFilterForm" autocomplete="off">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="search" class="form-label fw-bold">
                        <i class="bi bi-search"></i> {{ __('messages.common.search') }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control"
                               value="{{ request('search') }}"
                               placeholder="{{ __('messages.complaints.search_placeholder') }}">
                        <button type="button" class="btn btn-secondary" id="clearSearch" title="{{ __('messages.common.clear') }}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">
                        {{ __('messages.complaints.search_hint') }}
                    </small>
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label fw-bold">
                        <i class="bi bi-flag"></i> {{ __('messages.common.status') }}
                    </label>
                    <select name="status" id="status" class="form-select">
                        <option value="">{{ __('messages.super_admin.garages.all_statuses') }}</option>
                        @foreach(ComplaintStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>
                                {{ $s->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="complaint_type" class="form-label fw-bold">
                        <i class="bi bi-tag"></i> {{ __('messages.complaints.complaint_type') }}
                    </label>
                    <select name="complaint_type" id="complaint_type" class="form-select">
                        <option value="">{{ __('messages.super_admin.users.all_roles') }}</option>
                        @foreach(ComplaintType::cases() as $t)
                            <option value="{{ $t->value }}" @selected(request('complaint_type') === $t->value)>
                                {{ $t->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="yer" class="form-label fw-bold">
                        <i class="bi bi-geo-alt"></i> {{ __('messages.complaints.location') }}
                    </label>
                    <select name="yer" id="yer" class="form-select">
                        <option value="">{{ __('messages.super_admin.garages.all_statuses') }}</option>
                        @foreach(Location::cases() as $loc)
                            <option value="{{ $loc->value }}" @selected(request('yer') === $loc->value)>
                                {{ $loc->icon() }} {{ $loc->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-calendar-range"></i>
                        {{ __('messages.reports.period.from') }} / {{ __('messages.reports.period.to') }}
                    </label>
                    <div class="input-group">
                        <input type="date" name="date_from" class="form-control"
                               value="{{ request('date_from') }}">
                        <span class="input-group-text">—</span>
                        <input type="date" name="date_to" class="form-control"
                               value="{{ request('date_to') }}">
                    </div>
                </div>

                <div class="col-md-12 d-flex gap-2">
                    <a href="{{ route('complaints.index') }}" class="btn btn-secondary" id="resetButton">
                        <i class="bi bi-x-circle"></i> {{ __('messages.common.reset') }}
                    </a>
                    <span class="badge bg-info text-dark align-self-center" id="activeFilterBadge" style="display: none;">
                        <i class="bi bi-funnel-fill"></i> <span id="activeFilterCount">0</span>
                    </span>
                    <span class="text-muted small align-self-center ms-auto" id="searchStatus" style="display: none;">
                        <i class="bi bi-arrow-repeat"></i> {{ __('messages.common.loading') }}
                    </span>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ─── Results ─── --}}
<div id="searchResults">
    @include('complaints.partials.table', ['complaints' => $complaints])
</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    const form = document.getElementById('complaintFilterForm');
    const results = document.getElementById('searchResults');
    const totalCount = document.getElementById('totalCount');
    const searchStatus = document.getElementById('searchStatus');
    const activeFilterBadge = document.getElementById('activeFilterBadge');
    const activeFilterCount = document.getElementById('activeFilterCount');
    const clearSearch = document.getElementById('clearSearch');
    const resetButton = document.getElementById('resetButton');

    if (!form || !results) return;

    let searchTimeout = null;
    let currentRequest = 0;

    const FILTER_KEYS = ['search', 'status', 'complaint_type', 'yer', 'date_from', 'date_to'];

    function collectFilters() {
        const params = new URLSearchParams();

        FILTER_KEYS.forEach(key => {
            const el = form.querySelector(`[name="${key}"]`);
            if (!el) return;

            const value = (el.value || '').trim();
            if (value !== '') {
                params.set(key, value);
            }
        });

        return params;
    }

    function updateFilterBadge(params) {
        const count = params.toString() ? Array.from(params.keys()).length : 0;

        if (count > 0) {
            activeFilterCount.textContent = count;
            activeFilterBadge.style.display = 'inline-block';
        } else {
            activeFilterBadge.style.display = 'none';
        }
    }

    function performSearch() {
        const params = collectFilters();
        const queryString = params.toString();
        const requestId = ++currentRequest;

        // Show loading state
        searchStatus.style.display = 'inline-block';

        const fetchUrl = "{{ route('complaints.search') }}"
            + (queryString ? '?' + queryString : '');

        const browserUrl = "{{ route('complaints.index') }}"
            + (queryString ? '?' + queryString : '');

        fetch(fetchUrl, {
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
            // Ignore stale responses (fast typing race condition).
            if (requestId !== currentRequest) return;

            results.innerHTML = html;

            // Update the total counter from the freshly-rendered partial.
            const counter = results.querySelector('.total-count');
            if (counter && totalCount) {
                totalCount.textContent = counter.dataset.count || '0';
            }

            // Update the browser URL so that refresh / share keeps
            // the current filters.
            history.replaceState(null, '', browserUrl);

            updateFilterBadge(params);
        })
        .catch(error => {
            console.error('Complaint search error:', error);
        })
        .finally(() => {
            if (requestId === currentRequest) {
                searchStatus.style.display = 'none';
            }
        });
    }

    function scheduleSearch(delay) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, delay);
    }

    // ─── Bind filter inputs ───
    // Text input → debounce 300ms (avoid a request on every keystroke).
    // Selects and dates → fire immediately.
    const textInput = form.querySelector('input[name="search"]');
    if (textInput) {
        textInput.addEventListener('input', () => scheduleSearch(300));
    }

    form.querySelectorAll('select[name], input[type="date"]').forEach(el => {
        el.addEventListener('change', () => scheduleSearch(0));
    });

    // ─── Clear search only ───
    if (clearSearch) {
        clearSearch.addEventListener('click', () => {
            if (textInput) textInput.value = '';
            clearTimeout(searchTimeout);
            performSearch();
        });
    }

    // ─── Reset all filters — let the normal link navigation handle it
    // (goes to the plain /complaints URL, clearing the query string).

    // ─── Submit → intercept and route through AJAX ───
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        clearTimeout(searchTimeout);
        performSearch();
    });

    // ─── Initial badge state (when the page loads with filters) ───
    updateFilterBadge(collectFilters());
})();
</script>
@endsection