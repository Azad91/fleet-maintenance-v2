@extends('layouts.app')

@section('title', __('messages.buses.title'))

@section('content')
<div class="page-header">
    <h1>🚌 {{ __('messages.buses.title') }}</h1>
    <p class="text-muted">{{ __('messages.buses.subtitle') }}</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2 flex-wrap">
        @can('import', App\Models\Bus::class)
            <a href="{{ route('buses.import') }}" class="btn btn-success">
                <i class="bi bi-upload"></i> {{ __('messages.buses.import') }}
            </a>
        @endcan
        @can('update', App\Models\Bus::class)
            <button type="button" class="btn btn-warning" id="bulkDeactivateBtn" disabled>
                <i class="bi bi-x-circle"></i> {{ __('messages.buses.bulk_deactivate') }}
            </button>
            <button type="button" class="btn btn-info" id="bulkActivateBtn" disabled>
                <i class="bi bi-check-circle"></i> {{ __('messages.buses.bulk_activate') }}
            </button>
        @endcan
        @can('delete', App\Models\Bus::class)
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                <i class="bi bi-trash"></i> {{ __('messages.buses.bulk_delete') }}
            </button>
            <button type="button" class="btn btn-outline-danger" id="bulkDeleteAllBtn"
                    data-total="{{ $buses->total() }}"
                    {{ $buses->total() === 0 ? 'disabled' : '' }}>
                <i class="bi bi-trash-fill"></i>
                {{ __('messages.common.bulk_delete_all') }} ({{ $buses->total() }})
            </button>
        @endcan
        @can('create', App\Models\Bus::class)
            <a href="{{ route('buses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> {{ __('messages.buses.new') }}
            </a>
        @endcan
    </div>
</div>

<form id="bulkForm" method="POST">
    @csrf
    <input type="hidden" name="ids" id="selectedIds" value="">
</form>

<form id="bulkDeleteAllForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <div id="bulkDeleteAllFilters"></div>
</form>

<div id="searchResults">
    @include('buses.partials.table', [
        'buses' => $buses,
        'isEmpty' => $isEmpty ?? $buses->isEmpty(),
        'hasActiveFilters' => $hasActiveFilters ?? false,
    ])
</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    const selectedIds = new Set();
    const bulkDeactivateBtn = document.getElementById('bulkDeactivateBtn');
    const bulkActivateBtn = document.getElementById('bulkActivateBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkForm = document.getElementById('bulkForm');
    const selectedIdsInput = document.getElementById('selectedIds');

    const bulkDeleteAllBtn     = document.getElementById('bulkDeleteAllBtn');
    const bulkDeleteAllForm    = document.getElementById('bulkDeleteAllForm');
    const bulkDeleteAllFilters = document.getElementById('bulkDeleteAllFilters');
    const DELETE_ALL_LABEL     = @json(__('messages.common.bulk_delete_all'));

    const translations = {
        bulk_deactivate: @json(__('messages.buses.bulk_deactivate')),
        bulk_activate: @json(__('messages.buses.bulk_activate')),
        bulk_delete: @json(__('messages.buses.bulk_delete')),
        bulk_deactivate_confirm: @json(__('messages.buses.bulk_deactivate_confirm')),
        bulk_activate_confirm: @json(__('messages.buses.bulk_activate_confirm')),
        bulk_delete_confirm: @json(__('messages.buses.bulk_delete_confirm')),
    };

    function updateBulkButtons() {
        const count = selectedIds.size;
        if (bulkDeactivateBtn) {
            bulkDeactivateBtn.disabled = count === 0;
            bulkDeactivateBtn.innerHTML = `<i class="bi bi-x-circle"></i> ${translations.bulk_deactivate} (${count})`;
        }
        if (bulkActivateBtn) {
            bulkActivateBtn.disabled = count === 0;
            bulkActivateBtn.innerHTML = `<i class="bi bi-check-circle"></i> ${translations.bulk_activate} (${count})`;
        }
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = count === 0;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash"></i> ${translations.bulk_delete} (${count})`;
        }
    }

    function syncBulkDeleteAllButton() {
        if (! bulkDeleteAllBtn) return;

        const counter = document.querySelector('#searchResults .total-count');
        const newTotal = counter?.dataset.count || '0';

        bulkDeleteAllBtn.dataset.total = newTotal;
        bulkDeleteAllBtn.disabled = newTotal === '0';
        bulkDeleteAllBtn.innerHTML =
            '<i class="bi bi-trash-fill"></i> '
            + DELETE_ALL_LABEL + ' (' + newTotal + ')';
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('.bus-checkbox')) {
            const id = parseInt(e.target.value, 10);
            if (e.target.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            updateBulkButtons();
        }

        if (e.target.matches('#selectAll')) {
            const checkboxes = document.querySelectorAll('.bus-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = e.target.checked;
                const id = parseInt(cb.value, 10);
                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            });
            updateBulkButtons();
        }
    });

    function submitBulk(action, method, confirmMessage) {
        if (selectedIds.size === 0) return;
        if (!confirm(confirmMessage.replace(':count', selectedIds.size))) return;

        bulkForm.action = action;
        bulkForm.method = 'POST';

        bulkForm.querySelectorAll('input[name="_method"]').forEach(el => el.remove());

        if (method !== 'POST') {
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = method;
            bulkForm.appendChild(methodInput);
        }

        selectedIdsInput.value = JSON.stringify([...selectedIds]);
        bulkForm.submit();
    }

    bulkDeactivateBtn?.addEventListener('click', () => {
        submitBulk(
            "{{ route('buses.bulk.deactivate') }}",
            'POST',
            translations.bulk_deactivate_confirm
        );
    });

    bulkActivateBtn?.addEventListener('click', () => {
        submitBulk(
            "{{ route('buses.bulk.activate') }}",
            'POST',
            translations.bulk_activate_confirm
        );
    });

    bulkDeleteBtn?.addEventListener('click', () => {
        submitBulk(
            "{{ route('buses.bulk.delete') }}",
            'DELETE',
            translations.bulk_delete_confirm
        );
    });

    // ════════════════════════════════════════════════════════════════
    // DELETE ALL MATCHING FILTER
    // ════════════════════════════════════════════════════════════════

    if (bulkDeleteAllBtn && bulkDeleteAllForm && bulkDeleteAllFilters) {
        bulkDeleteAllBtn.addEventListener('click', () => {
            const total = parseInt(bulkDeleteAllBtn.dataset.total, 10) || 0;

            if (total === 0) return;

            const message = @json(__('messages.common.bulk_delete_all_confirm'))
                .replace(':count', total);

            if (! confirm(message)) return;

            bulkDeleteAllFilters.innerHTML = '';
            collectFilters().forEach((value, key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                bulkDeleteAllFilters.appendChild(input);
            });

            bulkDeleteAllForm.action = "{{ route('buses.bulk.delete-all') }}";
            bulkDeleteAllForm.submit();
        });
    }

    let searchTimeout = null;

    function collectFilters() {
        const filters = {};
        document.querySelectorAll('#busTableFilter input[name]').forEach(input => {
            const value = input.value.trim();
            if (value !== '') {
                filters[input.name] = value;
            }
        });
        return filters;
    }

    function performSearch() {
        const filters = collectFilters();
        const hasFilters = Object.keys(filters).length > 0;

        const params = new URLSearchParams(filters);
        const queryString = params.toString();

        const fetchUrl = "{{ route('buses.search') }}" + (queryString ? '?' + queryString : '');

        const browserUrl = (hasFilters
            ? "{{ route('buses.search') }}"
            : "{{ route('buses.index') }}"
        ) + (queryString ? '?' + queryString : '');

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
            document.getElementById('searchResults').innerHTML = html;

            history.replaceState(null, '', browserUrl);

            selectedIds.clear();
            updateBulkButtons();
            syncBulkDeleteAllButton();
        })
        .catch(error => {
            console.error('Bus search error:', error);
        });
    }

    function scheduleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('#busTableFilter input[name]')) {
            scheduleSearch();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.matches('#busTableFilter input[name]')) {
            e.preventDefault();
            clearTimeout(searchTimeout);
            performSearch();
        }
    });

    updateBulkButtons();
    syncBulkDeleteAllButton();
})();
</script>
@endsection
