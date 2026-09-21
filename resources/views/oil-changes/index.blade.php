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
        <a href="{{ route('oil-changes.urgent') }}" class="btn btn-danger">
            <i class="fas fa-triangle-exclamation"></i> {{ __('messages.oil_change.urgent_title') }}
        </a>
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
        'all'        => ['label' => __('messages.oil_change.all_statuses'),     'color' => 'secondary'],
        'overdue'    => ['label' => __('messages.oil_change.status.overdue'),   'color' => 'danger'],
        'critical'   => ['label' => __('messages.oil_change.status.critical'),  'color' => 'warning'],
        'due-soon'   => ['label' => __('messages.oil_change.status.due-soon'),  'color' => 'info'],
        'ok'         => ['label' => __('messages.oil_change.status.ok'),        'color' => 'success'],
        'no-history' => ['label' => __('messages.oil_change.status.no-history'),'color' => 'secondary'],
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
</div>

{{-- One section per oil type --}}
@foreach(OilType::cases() as $type)
    @php
        $sectionRows = $rows
            ->filter(function (array $row) use ($type, $statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }

                return $row['statuses'][$type->value]->status === $statusFilter;
            })
            ->sortBy(fn (array $row) => $row['statuses'][$type->value]->remainingKm ?? PHP_INT_MAX)
            ->values();

        $urgentCount = $sectionRows->filter(
            fn (array $row) => in_array($row['statuses'][$type->value]->status, ['overdue', 'critical'], true)
        )->count();
    @endphp

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong style="font-size: 15px;">
                    {{ $type->icon() }} {{ $type->label() }}
                </strong>
                <span class="badge bg-secondary ms-2">{{ $sectionRows->count() }}</span>
                @if($urgentCount > 0)
                    <a href="{{ route('oil-changes.urgent') }}" class="badge bg-danger text-decoration-none ms-1">
                        <i class="fas fa-triangle-exclamation"></i> {{ $urgentCount }} {{ __('messages.oil_change.urgent_short') }}
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.oil_change.bus') }}</th>
                            <th>{{ __('messages.oil_change.last_change_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.current_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.remaining_km') }}</th>
                            <th class="text-center">{{ __('messages.oil_change.column_status') }}</th>
                            <th class="text-end">{{ __('messages.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sectionRows as $row)
                            @php
                                $status = $row['statuses'][$type->value];
                                $bus = $row['bus'];
                            @endphp
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
                                <td>
                                    @if($status->lastChange)
                                        <strong>{{ number_format($status->lastChange->actual_km, 0, '', '.') }}</strong> km
                                        @if($status->lastChange->changed_at)
                                            <br>
                                            <small class="text-muted">
                                                {{ $status->lastChange->changed_at->format('d.m.Y') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">{{ __('messages.oil_change.no_history') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($status->currentKm, 0, '', '.') }}</strong> km
                                </td>
                                <td class="text-end">
                                    @if($status->nextDueKm)
                                        {{ number_format($status->nextDueKm, 0, '', '.') }} km
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($status->remainingKm === null)
                                        <span class="text-muted">—</span>
                                    @elseif($status->isOverdue())
                                        <span class="text-danger fw-bold">
                                            −{{ number_format($status->overdueByKm(), 0, '', '.') }} km
                                        </span>
                                    @else
                                        <strong>{{ number_format($status->remainingKm, 0, '', '.') }}</strong> km
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $status->bootstrapColor() }}">
                                        {{ $status->statusLabel() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        @if($status->lastChange)
                                            <a href="{{ route('oil-changes.edit', $status->lastChange) }}"
                                               class="btn btn-sm btn-outline-warning"
                                               title="{{ __('messages.common.edit') }}">
                                                <i class="fas fa-pencil"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('oil-changes.create', ['bus_id' => $bus->id, 'type' => $type->value]) }}"
                                           class="btn btn-sm btn-outline-success"
                                           title="{{ __('messages.oil_change.add_change') }}">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: .3;"></i>
                                    {{ __('messages.reports.content.no_data') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endsection
