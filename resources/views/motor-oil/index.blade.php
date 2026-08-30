@extends('layouts.app')

@section('title', 'Motor Yağ Detalları')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🛢️ Motor Yağ Detalları</h1>
    <a href="{{ route('motor-oil.import') }}" class="btn btn-success">
        <i class="bi bi-upload"></i> Excel - dən Yüklə
    </a>
</div>

<!-- Canlı Axtarış -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchInput"
                           placeholder="KM ilə axtar (məs: 36000, 72000...)"
                           oninput="liveSearch(this.value)">
                    <button class="btn btn-secondary" onclick="document.getElementById('searchInput').value=''; liveSearch('');">
                        <i class="bi bi-x-circle"></i> Təmizlə
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">🔍 KM yazmağa başlayın, avtomatik filtrasiya olunacaq</small>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">Toplam: <span id="totalCount">0</span> detal</small>
            </div>
        </div>
    </div>
</div>

<!-- Nəticələr -->
<div id="searchResults">
    @include('motor-oil.partials.table', ['grouped' => $grouped])
</div>
@endsection

@section('scripts')
<script>
    function liveSearch(query) {
        const params = new URLSearchParams();
        const normalized = query.replace(/[.,\s]/g, '');

        if (normalized) {
            params.set('search', normalized);
        }

        fetch('{{ url('/motor-oil/search') }}?' + params.toString())
            .then(response => response.text())
            .then(html => {
                document.getElementById('searchResults').innerHTML = html;
                const count = document.querySelector('#searchResults .total-count');
                if (count) {
                    document.getElementById('totalCount').textContent = count.dataset.count;
                }
            })
            .catch(error => console.error('Xəta:', error));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const count = document.querySelector('#searchResults .total-count');
        if (count) {
            document.getElementById('totalCount').textContent = count.dataset.count;
        }
    });
</script>
@endsection
