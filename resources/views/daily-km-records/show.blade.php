@extends('layouts.app')

@section('title', __('messages.daily_km.details'))

@section('content')
<div class="fleet-dashboard">
    {{-- ─── Page Heading ─── --}}
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.nav.daily_records') }}</span>
            <h1>{{ __('messages.daily_km.details') }}</h1>
            <p>
                <strong>{{ $record->bus->dqn ?? '—' }}</strong>
                @if($record->bus?->route_number)
                    · {{ __('messages.daily_km.route_label', ['route' => $record->bus->route_number]) }}
                @endif
                · {{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '—' }}
            </p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('daily-km-records.index') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
            <a href="{{ route('daily-km-records.create') }}?bus_id={{ $record->bus_id }}"
               class="fleet-button fleet-button--primary">
                <i class="fas fa-plus"></i> {{ __('messages.daily_km.add_for_bus') }}
            </a>
        </div>
    </section>

    {{-- ─── Record Info Panel ─── --}}
    <section class="fleet-panel mb-4">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.daily_km.details') }}</span>
                <h2>{{ __('messages.warehouse.basic_info') }}</h2>
            </div>
        </header>

        <div class="fleet-list">
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-bus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.daily_km.bus') }}</strong>
                    <small>{{ $record->bus->dqn ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-route"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.buses.route_number') }}</strong>
                    <small>{{ $record->bus->route_number ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.daily_km.date') }}</strong>
                    <small>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-gauge-high"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.daily_km.km') }}</strong>
                    <small><strong>{{ number_format($record->km, 0, ',', '.') }} km</strong></small>
                </span>
            </div>

            @if($record->notes)
                <div class="fleet-list__item">
                    <span class="fleet-list__icon"><i class="fas fa-comment"></i></span>
                    <span class="fleet-list__content">
                        <strong>{{ __('messages.daily_km.notes') }}</strong>
                        <small>{{ $record->notes }}</small>
                    </span>
                </div>
            @endif

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-plus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.created') }}</strong>
                    <small>{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>
        </div>
    </section>

    {{-- ─── KM History Panel ─── --}}
    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.daily_km.history') }}</span>
                <h2>
                    {{ __('messages.daily_km.history_for', ['dqn' => $record->bus->dqn]) }}
                    <span class="fleet-status fleet-status--muted ms-2">
                        {{ $history->count() }}
                    </span>
                </h2>
            </div>
        </header>

        <div class="fleet-table-wrap">
            <table class="fleet-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.daily_km.date') }}</th>
                        <th class="text-end">{{ __('messages.daily_km.km') }}</th>
                        <th>{{ __('messages.daily_km.notes') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('d.m.Y') : '—' }}</td>
                            <td class="text-end">
                                <strong>{{ number_format($item->km, 0, ',', '.') }} km</strong>
                            </td>
                            <td>{{ $item->notes ?? '—' }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('daily-km-records.show', $item) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="{{ __('messages.common.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('daily-km-records.edit', $item) }}"
                                       class="btn btn-sm btn-outline-warning"
                                       title="{{ __('messages.common.edit') }}">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="fleet-table__empty">
                                <i class="fas fa-gauge-high fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.daily_km.no_records_for_bus') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection