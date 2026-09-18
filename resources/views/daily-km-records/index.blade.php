@extends('layouts.app')

@section('title', __('messages.daily_km.title'))

@php
    use App\Enums\RoleEnum;

    $canManageDailyKm = auth()->user()?->isSuperAdmin()
        || auth()->user()?->hasGarageRole(array_merge([RoleEnum::ADMIN->value], RoleEnum::dailyKmRoles()));

    // When no explicit date filter is applied, the "delete all" button
    // reflects the TOTAL count across every date — clicking it wipes
    // the entire KM history for this garage.
    $deleteAllCount = ($dateWasExplicit ?? false)
        ? $records->total()
        : ($totalAll ?? 0);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>📊 {{ __('messages.daily_km.title') }}</h1>
    @if($canManageDailyKm)
    <div class="d-flex gap-2">
        @can('delete', App\Models\DailyKmRecord::class)
            <button type="button" class="btn btn-outline-danger" id="bulkDeleteAllBtn"
                    data-total="{{ $deleteAllCount }}"
                    data-date-explicit="{{ ($dateWasExplicit ?? false) ? '1' : '0' }}"
                    {{ $deleteAllCount === 0 ? 'disabled' : '' }}>
                <i class="bi bi-trash-fill"></i>
                {{ __('messages.common.bulk_delete_all') }} ({{ $deleteAllCount }})
            </button>
        @endcan
        <a href="{{ route('daily-km-records.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> {{ __('messages.daily_km.import') }}
        </a>
        <a href="{{ route('daily-km-records.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('messages.daily_km.new') }}
        </a>
    </div>
    @endif
</div>

<form id="bulkDeleteAllForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <div id="bulkDeleteAllFilters"></div>
</form>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('daily-km-records.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="date" class="form-label fw-bold">
                    <i class="bi bi-calendar-event"></i> {{ __('messages.daily_km.date') }}
                </label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $dateWasExplicit ? $date : '' }}">
                <small class="text-muted d-block mt-1">{{ __('messages.daily_km.filter_date_hint') }}</small>
            </div>
            <div class="col-md-4">
                <label for="dqn" class="form-label fw-bold">
                    <i class="bi bi-bus-front"></i> {{ __('messages.buses.dqn') }}
                </label>
                <input type="text" name="dqn" id="dqn" class="form-control"
                       value="{{ $dqn }}"
                       placeholder="{{ __('messages.buses.filter_dqn') }}"
                       autocomplete="off"
                       style="text-transform: uppercase;">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search"></i> {{ __('messages.common.filter') }}
                </button>
                <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary" title="{{ __('messages.common.reset') }}">
                    <i class="bi bi-x-circle"></i>
                </a>
                <a href="{{ route('daily-km-records.export', ['date' => $dateWasExplicit ? $date : '', 'dqn' => $dqn]) }}"
                   class="btn btn-outline-success"
                   title="{{ __('messages.common.export') }}">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.daily_km.bus') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.daily_km.date') }}</th>
                        <th>{{ __('messages.daily_km.km') }}</th>
                        <th>{{ __('messages.daily_km.notes') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>{{ $records->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $record->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $record->bus->route_number ?? '-' }}</td>
                        <td>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '-' }}</td>
                        <td><strong>{{ number_format($record->km, 0, ',', '.') }} km</strong></td>
                        <td>{{ $record->notes ?? '-' }}</td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('daily-km-records.show', $record) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $record)
                                    <a href="{{ route('daily-km-records.edit', $record) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $record)
                                    <form action="{{ route('daily-km-records.destroy', $record) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.common.confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-graph-up" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            {{ __('messages.daily_km.no_records') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->total() > 0)
            <div class="text-center text-muted small mt-2 mb-3">
                {{ __('messages.common.showing', [
                    'from'  => $records->firstItem(),
                    'to'    => $records->lastItem(),
                    'total' => $records->total(),
                ]) }}
            </div>
        @endif
    </div>
</div>

<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $records->withQueryString()->links() }}
</div>
@endsection

@section('scripts')
<script>
    (function () {
        const btn = document.getElementById('bulkDeleteAllBtn');
        const form = document.getElementById('bulkDeleteAllForm');
        const filtersContainer = document.getElementById('bulkDeleteAllFilters');

        if (! btn || ! form || ! filtersContainer) return;

        btn.addEventListener('click', () => {
            const total = parseInt(btn.dataset.total, 10) || 0;
            const dateExplicit = btn.dataset.dateExplicit === '1';

            if (total === 0) return;

            const message = @json(__('messages.common.bulk_delete_all_confirm'))
                .replace(':count', total);

            if (! confirm(message)) return;

            filtersContainer.innerHTML = '';

            // Only pass the date if the user explicitly filtered by it.
            // Otherwise, the controller deletes across ALL dates.
            if (dateExplicit) {
                const date = document.getElementById('date')?.value ?? '';
                if (date !== '') {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'date'; i.value = date;
                    filtersContainer.appendChild(i);
                }
            }

            const dqn = document.getElementById('dqn')?.value ?? '';

            if (dqn !== '') {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = 'dqn'; i.value = dqn;
                filtersContainer.appendChild(i);
            }

            form.action = "{{ route('daily-km-records.bulk.delete-all') }}";
            form.submit();
        });
    })();
</script>
@endsection
