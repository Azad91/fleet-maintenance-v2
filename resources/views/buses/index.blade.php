@extends('layouts.app')

@section('title', 'Buses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        @can('import', App\Models\Bus::class)
            <a href="{{ route('buses.import') }}" class="btn btn-success">
                <i class="bi bi-upload"></i> Import from Excel
            </a>
        @endcan
        @can('update', App\Models\Bus::class)
            <button type="button" class="btn btn-warning" id="bulkDeactivateBtn" disabled>
                <i class="bi bi-x-circle"></i> Deactivate Selected
            </button>
            <button type="button" class="btn btn-info" id="bulkActivateBtn" disabled>
                <i class="bi bi-check-circle"></i> Activate Selected
            </button>
        @endcan
        @can('delete', App\Models\Bus::class)
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                <i class="bi bi-trash"></i> Delete Selected
            </button>
        @endcan
    </div>
</div>

{{-- Bulk operations form --}}
<form id="bulkForm" method="POST">
    @csrf
    <input type="hidden" name="ids" id="selectedIds" value="">
</form>

{{-- Results --}}
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

    // ==================== BULK ACTIONS ====================
    const selectedIds = new Set();
    const bulkDeactivateBtn = document.getElementById('bulkDeactivateBtn');
    const bulkActivateBtn = document.getElementById('bulkActivateBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkForm = document.getElementById('bulkForm');
    const selectedIdsInput = document.getElementById('selectedIds');

    function updateBulkButtons() {
        const count = selectedIds.size;
        if (bulkDeactivateBtn) {
            bulkDeactivateBtn.disabled = count === 0;
            bulkDeactivateBtn.innerHTML = `<i class="bi bi-x-circle"></i> Deactivate Selected (${count})`;
        }
        if (bulkActivateBtn) {
            bulkActivateBtn.disabled = count === 0;
            bulkActivateBtn.innerHTML = `<i class="bi bi-check-circle"></i> Activate Selected (${count})`;
        }
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = count === 0;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash"></i> Delete Selected (${count})`;
        }
    }

    // Event delegation — dynamically replaced content üçün
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
        if (!confirm(confirmMessage)) return;

        bulkForm.action = action;
        bulkForm.method = 'POST';

        // Köhnə _method inputunu təmizlə
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
            `Are you sure you want to deactivate ${selectedIds.size} bus(es)?`
        );
    });

    bulkActivateBtn?.addEventListener('click', () => {
        submitBulk(
            "{{ route('buses.bulk.activate') }}",
            'POST',
            `Are you sure you want to activate ${selectedIds.size} bus(es)?`
        );
    });

    bulkDeleteBtn?.addEventListener('click', () => {
        submitBulk(
            "{{ route('buses.bulk.delete') }}",
            'DELETE',
            `Are you sure you want to DELETE ${selectedIds.size} bus(es)? THIS CANNOT BE UNDONE!`
        );
    });

    // ==================== LIVE SEARCH ====================
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

        // AJAX həmişə /buses/search-ə gedir
        const fetchUrl = "{{ route('buses.search') }}" + (queryString ? '?' + queryString : '');

        // URL çubuğunda göstərilən URL — filter yoxdursa, /buses
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

            // URL-i yenilə (reload etmədən)
            history.replaceState(null, '', browserUrl);

            // Bulk action seçimini sıfırla (yeni nəticələr gəldi)
            selectedIds.clear();
            updateBulkButtons();
        })
        .catch(error => {
            console.error('Bus search error:', error);
        });
    }

    function scheduleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    }

    // Filter input-larına listener qoş (event delegation)
    document.addEventListener('input', function (e) {
        if (e.target.matches('#busTableFilter input[name]')) {
            scheduleSearch();
        }
    });

    // Enter basıldıqda dərhal axtar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.matches('#busTableFilter input[name]')) {
            e.preventDefault();
            clearTimeout(searchTimeout);
            performSearch();
        }
    });

    updateBulkButtons();
})();
</script>
@endsection
