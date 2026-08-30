@extends('layouts.app')

@section('title', 'Anbar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>📦 Anbar</h1>
    <div>
        <a href="{{ route('warehouses.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Yeni Məhsul
        </a>
    </div>
</div>

<!-- Canlı Axtarış -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Kod və ya Ad ilə axtar..." oninput="liveSearch(this.value)">
                    <button class="btn btn-secondary" onclick="document.getElementById('searchInput').value=''; liveSearch('');">
                        <i class="bi bi-x-circle"></i> Təmizlə
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">Kod (D-001) və ya Ad (Filtr) ilə axtar</small>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">Toplam: <span id="totalCount">{{ $warehouses->count() }}</span> məhsul</small>
            </div>
        </div>
    </div>
</div>

<!-- Nəticələr -->
<div id="searchResults">
    @include('warehouses.partials.table', ['warehouses' => $warehouses])
</div>
@endsection

@section('scripts')
<script>
    function liveSearch(query) {
        const params = new URLSearchParams();
        const search = query.trim();

        if (search) {
            params.set('search', search);
        }

        fetch('{{ url('/warehouses/search') }}?' + params.toString())
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
</script>
@endsection
