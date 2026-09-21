@extends('layouts.app')

@section('title', $bus->dqn . ' · ' . __('messages.oil_change.title'))

@php
    use App\Enums\OilType;
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-1">🚌 {{ $bus->dqn }}</h1>
        <p class="text-muted mb-0">
            @if($bus->route_number)
                {{ __('messages.daily_km.route_label', ['route' => $bus->route_number]) }}
                ·
            @endif
            {{ $bus->bus_project ?? '—' }}
            · <strong>{{ number_format($bus->latestKmRecord?->km ?? $bus->km ?? 0, 0, '', '.') }} km</strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('oil-changes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
        <a href="{{ route('oil-changes.create', ['bus_id' => $bus->id]) }}"
           class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('messages.oil_change.add_change') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{{ session('success') }}
    </div>
@endif

@foreach(OilType::cases() as $type)
    @php
        $status = $statuses[$type->value] ?? null;
        $history = $bus->oilChanges->where('oil_type', $type)->sortByDesc('actual_km');
    @endphp

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $type->icon() }} {{ $type->label() }}</strong>
                @if($status)
                    <span class="badge bg-{{ $status->bootstrapColor() }} ms-2">
                        {{ $status->statusLabel() }}
                    </span>
                @endif
            </div>
            @if($status && $status->remainingKm !== null)
                <span class="text-muted small">
                    {{ __('messages.oil_change.next_due_km') }}:
                    <strong>{{ number_format($status->nextDueKm, 0, '', '.') }} km</strong>
                    ({{ $status->remainingKm >= 0 ? '+' : '' }}{{ number_format($status->remainingKm, 0, '', '.') }})
                </span>
            @endif
        </div>

        <div class="card-body p-0">
            @if($history->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>{{ __('messages.oil_change.scheduled_km') }}</th>
                                <th>{{ __('messages.oil_change.actual_km') }}</th>
                                <th>{{ __('messages.oil_change.interval_km') }}</th>
                                <th>{{ __('messages.oil_change.brand') }}</th>
                                <th>{{ __('messages.oil_change.changed_at') }}</th>
                                <th>{{ __('messages.oil_change.notes') }}</th>
                                <th class="text-end">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $index => $change)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $change->scheduled_km ? number_format($change->scheduled_km, 0, '', '.') . ' km' : '—' }}</td>
                                    <td><strong>{{ number_format($change->actual_km, 0, '', '.') }} km</strong></td>
                                    <td>{{ number_format($change->interval_km, 0, '', '.') }} km</td>
                                    <td>
                                        @if($change->oil_brand)
                                            <span class="badge bg-info text-dark">{{ $change->oil_brand }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $change->changed_at?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($change->notes ?? '—', 40) }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('oil-changes.edit', $change) }}"
                                               class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-pencil"></i>
                                            </a>
                                            <form action="{{ route('oil-changes.destroy', $change) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('{{ __('messages.common.confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: .3;"></i>
                    {{ __('messages.oil_change.no_changes') }}
                </div>
            @endif
        </div>
    </div>
@endforeach
@endsection
