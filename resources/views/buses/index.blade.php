@extends('layouts.app')

@section('title', 'Avtobuslar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('buses.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
        @can('update', App\Models\Bus::class)
            <button type="button" class="btn btn-warning" id="bulkDeactivateBtn" disabled>
                <i class="bi bi-x-circle"></i> Seçilənləri Passiv Et
            </button>
            <button type="button" class="btn btn-info" id="bulkActivateBtn" disabled>
                <i class="bi bi-check-circle"></i> Seçilənləri Aktiv Et
            </button>
        @endcan
        @can('delete', App\Models\Bus::class)
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                <i class="bi bi-trash"></i> Seçilənləri Sil
            </button>
        @endcan
    </div>
</div>

<!-- Bulk əməliyyatlar üçün form -->
<form id="bulkForm" method="POST">
    @csrf
    @method('POST')
    <input type="hidden" name="ids" id="selectedIds" value="">
</form>

<!-- Nəticələr -->
<div id="searchResults">
    @include('buses.partials.table', ['buses' => $buses])
</div>

<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $buses->withQueryString()->links() }}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectedIds = new Set();
        const bulkDeactivateBtn = document.getElementById('bulkDeactivateBtn');
        const bulkActivateBtn = document.getElementById('bulkActivateBtn');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkForm = document.getElementById('bulkForm');
        const selectedIdsInput = document.getElementById('selectedIds');

        function updateButtons() {
            const count = selectedIds.size;
            bulkDeactivateBtn.disabled = count === 0;
            bulkActivateBtn.disabled = count === 0;
            bulkDeleteBtn.disabled = count === 0;
            bulkDeactivateBtn.textContent = `Seçilənləri Passiv Et (${count})`;
            bulkActivateBtn.textContent = `Seçilənləri Aktiv Et (${count})`;
            bulkDeleteBtn.textContent = `Seçilənləri Sil (${count})`;
        }

        // Checkbox event-ləri
        document.addEventListener('change', function(e) {
            if (e.target.matches('.bus-checkbox')) {
                const id = parseInt(e.target.value);
                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateButtons();
            }

            // Hamısını seç
            if (e.target.matches('#selectAll')) {
                const checkboxes = document.querySelectorAll('.bus-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                    const id = parseInt(cb.value);
                    if (e.target.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                updateButtons();
            }
        });

        // Bulk Deactivate
        bulkDeactivateBtn.addEventListener('click', function() {
            if (selectedIds.size === 0) return;
            if (!confirm(`${selectedIds.size} avtobusu passiv etmək istədiyinizə əminsiniz?`)) return;

            bulkForm.action = "{{ route('buses.bulk.deactivate') }}";
            selectedIdsInput.value = JSON.stringify([...selectedIds]);
            bulkForm.submit();
        });

        // Bulk Activate
        bulkActivateBtn.addEventListener('click', function() {
            if (selectedIds.size === 0) return;
            if (!confirm(`${selectedIds.size} avtobusu aktiv etmək istədiyinizə əminsiniz?`)) return;

            bulkForm.action = "{{ route('buses.bulk.activate') }}";
            selectedIdsInput.value = JSON.stringify([...selectedIds]);
            bulkForm.submit();
        });

        // Bulk Delete
        bulkDeleteBtn.addEventListener('click', function() {
            if (selectedIds.size === 0) return;
            if (!confirm(`${selectedIds.size} avtobusu silmək istədiyinizə əminsiniz? BU ƏMƏLİYYAT GERİ DÖNDƏRİLƏ BİLMƏZ!`)) return;

            bulkForm.action = "{{ route('buses.bulk.delete') }}";
            bulkForm.method = "POST";
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            bulkForm.appendChild(methodInput);
            selectedIdsInput.value = JSON.stringify([...selectedIds]);
            bulkForm.submit();
        });

        updateButtons();
    });
</script>
@endsection
