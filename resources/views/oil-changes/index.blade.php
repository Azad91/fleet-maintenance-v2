@extends('layouts.app')

@section('title', __('messages.oil_change.title'))

@php
    use App\Enums\OilType;
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-1">🛢️ {{ __('messages.oil_change.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.oil_change.subtitle') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('oil-changes.import') }}" class="btn btn-success">
            <i class="fas fa-upload"></i> {{ __('messages.oil_change.import_title') }}
        </a>
        <a href="{{ route('oil-changes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('messages.oil_change.new') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{{ session('success') }}
    </div>
@endif

@if(session('warning'))
    <div class="fleet-alert fleet-alert--warning">
        <i class="fas fa-triangle-exclamation"></i>{{ session('warning') }}
    </div>
@endif

{{-- Status filter chips --}}
@php
    $statusChips = [
        'all'        => ['label' => __('messages.oil_change.all_statuses'),    'color' => 'secondary'],
        'overdue'    => ['label' => __('messages.oil_change.status.overdue'),  'color' => 'danger'],
        'critical'   => ['label' => __('messages.oil_change.status.critical'), 'color' => 'warning'],
        'due-soon'   => ['label' => __('messages.oil_change.status.due-soon'), 'color' => 'info'],
        'ok'         => ['label' => __('messages.oil_change.status.ok'),       'color' => 'success'],
        'no-history' => ['label' => __('messages.oil_change.status.no-history'), 'color' => 'secondary'],
    ];
@endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($statusChips as $key => $chip)
        @php $isActive = $statusFilter === $key; @endphp
        <a href="{{ route('oil-changes.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $isActive ? 'btn-' . $chip['color'] : 'btn-outline-' . $chip['color'] }}">
            {{ $chip['label'] }}
        </a>
    @endforeach

    <span class="ms-auto align-self-center text-muted small">
        {{ __('messages.common.total') }}: <strong>{{ $rows->count() }}</strong>
    </span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 oil-change-table">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" style="vertical-align: middle;">
                            {{ __('messages.oil_change.bus') }}
                        </th>
                        <th colspan="3" class="text-center" style="border-left: 2px solid #e5e7eb;">
                            🛢️ {{ __('messages.oil_change.motor') }}
                        </th>
                        <th colspan="3" class="text-center" style="border-left: 2px solid #e5e7eb;">
                            ⚙️ {{ __('messages.oil_change.gearbox') }}
                        </th>
                        <th colspan="3" class="text-center" style="border-left: 2px solid #e5e7eb;">
                            🔩 {{ __('messages.oil_change.axle') }}
                        </th>
                        <th rowspan="2" class="text-end" style="vertical-align: middle;">
                            {{ __('messages.common.actions') }}
                        </th>
                    </tr>
                    <tr class="table-light" style="font-size: 10px;">
                        {{-- Motor --}}
                        <th class="text-end" style="border-left: 2px solid #e5e7eb;">
                            {{ __('messages.oil_change.current_km') }}
                        </th>
                        <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                        <th class="text-center">{{ __('messages.oil_change.column_status') }}</th>

                        {{-- Gearbox --}}
                        <th class="text-end" style="border-left: 2px solid #e5e7eb;">
                            {{ __('messages.oil_change.current_km') }}
                        </th>
                        <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                        <th class="text-center">{{ __('messages.oil_change.column_status') }}</th>

                        {{-- Axle --}}
                        <th class="text-end" style="border-left: 2px solid #e5e7eb;">
                            {{ __('messages.oil_change.current_km') }}
                        </th>
                        <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                        <th class="text-center">{{ __('messages.oil_change.column_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $bus = $row['bus']; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('oil-changes.show', $bus) }}" class="text-decoration-none">
                                    <strong>{{ $bus->dqn }}</strong>
                                </a>
                                @if($bus->route_number)
                                    <br>
                                    <small class="text-muted">
                                        {{ __('messages.daily_km.route_label', ['route' => $bus->route_number]) }}
                                    </small>
                                @endif
                            </td>

                            @foreach(OilType::cases() as $type)
                                @php
                                    $status = $row['statuses'][$type->value];
                                    $borderStyle = 'border-left: 2px solid #e5e7eb;';
                                @endphp

                                {{-- Last actual / current km --}}
                                <td class="text-end" style="{{ $borderStyle }}">
                                    @if($status->lastChange)
                                        <strong>{{ number_format($status->lastChange->actual_km, 0, '', '.') }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            → {{ number_format($status->currentKm, 0, '', '.') }}
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Next due / remaining km --}}
                                <td class="text-end">
                                    @if($status->nextDueKm)
                                        {{ number_format($status->nextDueKm, 0, '', '.') }}
                                        <br>
                                        @if($status->isOverdue())
                                            <small class="text-danger fw-bold">
                                                −{{ number_format($status->overdueByKm(), 0, '', '.') }}
                                            </small>
                                        @else
                                            <small class="text-muted">
                                                +{{ number_format($status->remainingKm, 0, '', '.') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Status badge --}}
                                <td class="text-center">
                                    <span class="badge bg-{{ $status->bootstrapColor() }}"
                                          style="font-size: 9px; padding: 3px 6px;">
                                        {{ $status->statusLabel() }}
                                    </span>
                                </td>
                            @endforeach

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('oil-changes.show', $bus) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="{{ __('messages.oil_change.history') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('oil-changes.create', ['bus_id' => $bus->id]) }}"
                                       class="btn btn-sm btn-outline-success"
                                       title="{{ __('messages.oil_change.add_change') }}">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="fas fa-oil-can fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.oil_change.no_priority_items') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .oil-change-table th,
    .oil-change-table td {
        font-size: 12px;
        padding: 8px 10px;
        vertical-align: middle;
    }

    .oil-change-table thead th {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    html[data-fleet-theme="dark"] .oil-change-table thead th[style*="border-left"] {
        border-color: #2c3c51 !important;
    }

    html[data-fleet-theme="dark"] .oil-change-table td[style*="border-left"] {
        border-color: #2c3c51 !important;
    }
</style>
@endpush
