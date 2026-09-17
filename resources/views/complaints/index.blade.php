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

{{-- ─── Actions row ─── --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
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

        {{-- ─── Bulk delete button ─── --}}
        @can('delete', App\Models\Complaint::class)
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                <i class="bi bi-trash"></i> {{ __('messages.complaints.bulk_delete') }}
            </button>
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

{{-- Hidden form used to submit the bulk-delete request --}}
<form id="bulkDeleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="ids" id="bulkSelectedIds" value="">
</form>

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

    const form            = document.getElementById('complaintFilterForm');
    const results         = document.getElementById('searchResults');
    const totalCount      = document.getElementById('totalCount');
    const searchStatus    = document.getElementById('searchStatus');
    const activeFilterBadge = document.getElementById('activeFilterBadge');
    const activeFilterCount = document.getElementById('activeFilterCount');
    const clearSearch     = document.getElementById('clearSearch');
    const bulkDeleteBtn   = document.getElementById('bulkDeleteBtn');
    const bulkDeleteForm  = document.getElementById('bulkDeleteForm');
    const bulkSelectedIds = document.getElementById('bulkSelectedIds');

    if (!form || !results) return;

    // ─── Selected IDs — kept in a Set so it survives pagination ───
    const selectedIds = new Set();

    let searchTimeout = null;
    let currentRequest = 0;

    const FILTER_KEYS = ['search', 'status', 'complaint_type', 'yer', 'date_from', 'date_to'];

    // ════════════════════════════════════════════════════════════════
    // SEARCH
    // ════════════════════════════════════════════════════════════════

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
            if (requestId !== currentRequest) return;

            results.innerHTML = html;

            const counter = results.querySelector('.total-count');
            if (counter && totalCount) {
                totalCount.textContent = counter.dataset.count || '0';
            }

            history.replaceState(null, '', browserUrl);

            updateFilterBadge(params);

            // Re-sync checkbox state after the table is re-rendered.
            syncCheckboxState();
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

    // ════════════════════════════════════════════════════════════════
    // BULK SELECTION
    // ════════════════════════════════════════════════════════════════

    function updateBulkButton() {
        if (!bulkDeleteBtn) return;

        const count = selectedIds.size;

        bulkDeleteBtn.disabled = count === 0;
        bulkDeleteBtn.innerHTML =
            '<i class="bi bi-trash"></i> '
            + @json(__('messages.complaints.bulk_delete'))
            + ' (' + count + ')';
    }

    /**
     * Restore the checked state on the freshly-rendered table.
     * Called after every AJAX search so that selections survive
     * pagination and filter changes.
     */
    function syncCheckboxState() {
        const checkboxes = results.querySelectorAll('.complaint-checkbox');

        checkboxes.forEach(cb => {
            const id = parseInt(cb.value, 10);
            cb.checked = selectedIds.has(id);
        });

        // Update the "select all" header checkbox.
        const selectAll = document.getElementById('selectAllComplaints');
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0
                && Array.from(checkboxes).every(cb => cb.checked);
        }

        updateBulkButton();
    }

    // Delegated events — survive table re-renders.
    results.addEventListener('change', (e) => {
        if (e.target.matches('.complaint-checkbox')) {
            const id = parseInt(e.target.value, 10);

            if (e.target.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }

            syncCheckboxState();
            return;
        }

        if (e.target.matches('#selectAllComplaints')) {
            const checkboxes = results.querySelectorAll('.complaint-checkbox');

            checkboxes.forEach(cb => {
                const id = parseInt(cb.value, 10);

                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            });

            syncCheckboxState();
        }
    });

    // ─── Bulk delete submit ───
    if (bulkDeleteBtn && bulkDeleteForm && bulkSelectedIds) {
        bulkDeleteBtn.addEventListener('click', () => {
            if (selectedIds.size === 0) return;

            const message = @json(__('messages.complaints.bulk_delete_confirm'))
                .replace(':count', selectedIds.size);

            if (! confirm(message)) return;

            bulkSelectedIds.value = JSON.stringify(Array.from(selectedIds));

            // IMPORTANT: the form already contains _method=DELETE,
            // so no need to inject it here.
            bulkDeleteForm.action = "{{ route('complaints.bulk.delete') }}";
            bulkDeleteForm.submit();
        });
    }

    // ════════════════════════════════════════════════════════════════
    // FILTER BINDINGS
    // ════════════════════════════════════════════════════════════════

    const textInput = form.querySelector('input[name="search"]');
    if (textInput) {
        textInput.addEventListener('input', () => scheduleSearch(300));
    }

    form.querySelectorAll('select[name], input[type="date"]').forEach(el => {
        el.addEventListener('change', () => scheduleSearch(0));
    });

    if (clearSearch) {
        clearSearch.addEventListener('click', () => {
            if (textInput) textInput.value = '';
            clearTimeout(searchTimeout);
            performSearch();
        });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        clearTimeout(searchTimeout);
        performSearch();
    });

    // ════════════════════════════════════════════════════════════════
    // INIT
    // ════════════════════════════════════════════════════════════════

    updateFilterBadge(collectFilters());
    syncCheckboxState();
})();
</script>
@endsection