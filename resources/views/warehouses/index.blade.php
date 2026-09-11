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

        if (search) {
            params.set('search', search);
        }

        fetch('{{ url('/warehouses/search') }}?' + params.toString(), {
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
                document.getElementById('searchResults').innerHTML = html;
                const count = document.querySelector('#searchResults .total-count');
                if (count) {
                    document.getElementById('totalCount').textContent = count.dataset.count || '0';
                }
            })
            .catch(error => console.error('Warehouse search error:', error));
    }
</script>
@endsection
