@extends('layouts.app')

@section('title', __('messages.daily_status.details'))

@section('content')
<div class="fleet-dashboard">
    {{-- ─── Page Heading ─── --}}
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.nav.daily_records') }}</span>
            <h1>{{ __('messages.daily_status.details') }}</h1>
            <p>
                <strong>{{ $status->bus->dqn ?? '—' }}</strong>
                @if($status->bus?->route_number)
                    · {{ __('messages.daily_km.route_label', ['route' => $status->bus->route_number]) }}
                @endif
                · {{ $status->date ? \Carbon\Carbon::parse($status->date)->format('d.m.Y') : '—' }}
            </p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('bus-daily-statuses.index') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
        </div>
    </section>

    {{-- ─── Status Info Panel ─── --}}
    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.daily_status.details') }}</span>
                <h2>{{ __('messages.warehouse.basic_info') }}</h2>
            </div>
        </header>

        <div class="fleet-list">
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-bus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.daily_status.bus') }}</strong>
                    <small>{{ $status->bus->dqn ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-route"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.buses.route_number') }}</strong>
                    <small>{{ $status->bus->route_number ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.daily_status.date') }}</strong>
                    <small>{{ $status->date ? \Carbon\Carbon::parse($status->date)->format('d.m.Y') : '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-flag"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.daily_status.status') }}</strong>
                    <small>
                        <span class="fleet-status fleet-status--warning">{{ $status->status }}</span>
                    </small>
                </span>
            </div>

            @if($status->notes)
                <div class="fleet-list__item">
                    <span class="fleet-list__icon"><i class="fas fa-comment"></i></span>
                    <span class="fleet-list__content">
                        <strong>{{ __('messages.daily_status.notes') }}</strong>
                        <small>{{ $status->notes }}</small>
                    </span>
                </div>
            @endif

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-plus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.created') }}</strong>
                    <small>{{ $status->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.last_updated') }}</strong>
                    <small>{{ $status->updated_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>
        </div>
    </section>
</div>
@endsection