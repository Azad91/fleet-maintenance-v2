@extends('layouts.app')

@section('title', 'Motor Yağı')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🛢️ Motor Yağı</h1>
    <div>
        <a href="{{ route('motor-oil.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Axtarış -->
        <div class="mb-3">
            <form action="{{ route('motor-oil.search') }}" method="GET" class="row g-2">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="🔍 KM üzrə axtar..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Axtar
                    </button>
                </div>
            </form>
        </div>

        <div id="motorOilResults">
            @include('motor-oil.partials.table', ['grouped' => $grouped])
        </div>
    </div>
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
