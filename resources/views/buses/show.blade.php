@extends('layouts.app')

@section('title', __('messages.buses.details_title'))

@section('content')
<div class="container-fluid">
    {{-- ─── Header ─── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                🚌 {{ $bus->dqn }}
                @if($bus->route_number)
                    <small class="text-muted">· {{ __('messages.daily_km.route_label', ['route' => $bus->route_number]) }}</small>
                @endif
            </h1>
            <p class="text-muted mb-0">
                {{ $bus->bus_project ?? __('messages.dashboard.model_not_specified') }}
                @if($bus->is_active)
                    · <span class="badge bg-success">{{ __('messages.buses.status_active') }}</span>
                @else
                    · <span class="badge bg-secondary">{{ __('messages.buses.status_inactive') }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('buses.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
    </div>

    {{-- ─── Tabs ─── --}}
    <ul class="nav nav-tabs mb-3" id="busTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab"
                    data-bs-toggle="tab" data-bs-target="#general-pane"
                    type="button" role="tab"
                    data-tab-key="general">
                <i class="bi bi-info-circle"></i> {{ __('messages.buses.tab_general') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="km-tab"
                    data-bs-toggle="tab" data-bs-target="#km-pane"
                    type="button" role="tab"
                    data-tab-key="km">
                <i class="bi bi-speedometer2"></i> {{ __('messages.buses.tab_km_history') }}
                <span class="badge bg-secondary ms-1">{{ $kmRecords->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="status-tab"
                    data-bs-toggle="tab" data-bs-target="#status-pane"
                    type="button" role="tab"
                    data-tab-key="status">
                <i class="bi bi-flag"></i> {{ __('messages.buses.tab_status_history') }}
                <span class="badge bg-secondary ms-1">{{ $statusRecords->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="busTabContent">
        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB 1: General info                                 --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="general-pane" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.bus_project') }}</small>
                            <strong>{{ $bus->bus_project ?? '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.vin') }}</small>
                            <strong>{{ $bus->vin ?? '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.length') }}</small>
                            <strong>{{ $bus->uzunluq ? number_format($bus->uzunluq, 1) . ' m' : '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.route_number') }}</small>
                            <strong>{{ $bus->route_number ?? '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.dqn') }}</small>
                            <strong>{{ $bus->dqn }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.engine_number') }}</small>
                            <strong>{{ $bus->engine_number ?? '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.km') }}</small>
                            <strong>
                                @if($bus->latestKmRecord)
                                    {{ number_format($bus->latestKmRecord->km, 0, ',', '.') }} km
                                    <small class="text-muted">({{ $bus->latestKmRecord->date->format('d.m.Y') }})</small>
                                @else
                                    {{ $bus->km ? number_format($bus->km, 0, ',', '.') . ' km' : '—' }}
                                @endif
                            </strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.buses.status') }}</small>
                            <strong>
                                @if($bus->is_active)
                                    <span class="badge bg-success">{{ __('messages.buses.status_active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.buses.status_inactive') }}</span>
                                @endif
                            </strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">{{ __('messages.complaints.created') }}</small>
                            <strong>{{ $bus->created_at?->format('d.m.Y H:i') ?? '—' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB 2: KM history                                  --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="km-pane" role="tabpanel">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>{{ __('messages.daily_km.date') }}</th>
                                    <th class="text-end">{{ __('messages.daily_km.km') }}</th>
                                    <th>{{ __('messages.daily_km.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kmRecords as $index => $record)
                                    <tr>
                                        <td>{{ $kmRecords->firstItem() + $index }}</td>
                                        <td>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '—' }}</td>
                                        <td class="text-end"><strong>{{ number_format($record->km, 0, ',', '.') }} km</strong></td>
                                        <td>{{ $record->notes ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="bi bi-speedometer2" style="font-size: 40px; display: block; margin-bottom: 10px; opacity: .3;"></i>
                                            {{ __('messages.buses.no_km_records') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($kmRecords->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $kmRecords->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB 3: Status history                              --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="status-pane" role="tabpanel">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>{{ __('messages.daily_status.date') }}</th>
                                    <th>{{ __('messages.daily_status.status') }}</th>
                                    <th>{{ __('messages.daily_status.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($statusRecords as $index => $record)
                                    <tr>
                                        <td>{{ $statusRecords->firstItem() + $index }}</td>
                                        <td>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '—' }}</td>
                                        <td>
                                            <span class="badge bg-secondary text-white" style="font-size: 13px; padding: 6px 12px; border-radius: 6px;">
                                                {{ $record->status }}
                                            </span>
                                        </td>
                                        <td>{{ $record->notes ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="bi bi-flag" style="font-size: 40px; display: block; margin-bottom: 10px; opacity: .3;"></i>
                                            {{ __('messages.buses.no_status_records') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($statusRecords->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $statusRecords->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Persist the active tab in the URL hash so that:
    //   1. Reloading the page keeps the user on the same tab.
    //   2. Copying the link (e.g. #km) opens directly on that tab.
    //   3. Paginating through KM history does not silently reset
    //      back to the General tab.
    (function () {
        const hash = window.location.hash.replace('#', '');
        if (hash === 'km' || hash === 'status' || hash === 'general') {
            const trigger = document.querySelector(`#busTabs [data-tab-key="${hash}"]`);
            if (trigger) {
                new bootstrap.Tab(trigger).show();
            }
        }

        document.querySelectorAll('#busTabs [data-tab-key]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', (e) => {
                const key = e.target.getAttribute('data-tab-key');
                history.replaceState(null, '', '#' + key);
            });
        });
    })();
</script>
@endsection