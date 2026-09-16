@extends('layouts.app')

@section('title', __('messages.warehouse.title'))

@section('content')
<div class="page-header">
    <h1>📦 {{ __('messages.warehouse.title') }}</h1>
    <p class="text-muted">{{ __('messages.warehouse.subtitle') }}</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2 flex-wrap">
        @can('import', App\Models\Warehouse::class)
            <a href="{{ route('warehouses.import') }}" class="btn btn-success">
                <i class="bi bi-upload"></i> {{ __('messages.warehouse.import') }}
            </a>
        @endcan
        @can('create', App\Models\Warehouse::class)
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> {{ __('messages.warehouse.new') }}
            </a>
        @endcan
    </div>
</div>
{{-- ─── View tabs: Active / Quarantine ─── --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ ($view ?? 'active') === 'active' ? 'active' : '' }}"
           href="{{ route('warehouses.index', ['view' => 'active']) }}">
            <i class="bi bi-box-seam"></i> {{ __('messages.warehouse.active_stock') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($view ?? '') === 'quarantine' ? 'active' : '' }}"
           href="{{ route('warehouses.index', ['view' => 'quarantine']) }}">
            <i class="bi bi-shield-exclamation"></i> {{ __('messages.warehouse.quarantine') }}
            @if(($quarantineCount ?? 0) > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $quarantineCount }}</span>
            @endif
        </a>
    </li>
</ul>

{{-- Search --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="{{ __('messages.warehouse.search_placeholder') }}"
                        value="{{ $search ?? '' }}"
                        oninput="liveSearch(this.value)">
                    <input type="hidden" id="viewInput" value="{{ $view ?? 'active' }}">
                    <button class="btn btn-secondary" type="button"
                            onclick="document.getElementById('searchInput').value=''; liveSearch('');">
                        <i class="bi bi-x-circle"></i> {{ __('messages.common.clear') }}
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">{{ __('messages.warehouse.search_hint') }}</small>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">
                    {{ __('messages.common.total') }}: <span id="totalCount">{{ $warehouses->count() }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

<div id="searchResults">
    @include('warehouses.partials.table', ['warehouses' => $warehouses])
</div>
@endsection

@section('scripts')
<script>
    function liveSearch(query) {
        const params = new URLSearchParams();
        const search = (query ?? '').trim();
        const view = document.getElementById('viewInput')?.value ?? 'active';

        if (search) params.set('search', search);
        params.set('view', view);

        fetch('{{ url('/warehouses/search') }}?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('searchResults').innerHTML = html;
            const count = document.querySelector('#searchResults .total-count');
            if (count) document.getElementById('totalCount').textContent = count.dataset.count || '0';
        })
        .catch(e => console.error('Warehouse search error:', e));
    }
</script>
@endsection
