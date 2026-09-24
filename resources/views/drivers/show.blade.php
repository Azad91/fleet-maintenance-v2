@extends('layouts.app')

@section('title', __('messages.drivers.details'))

@section('content')
<div class="fleet-dashboard">
    {{-- ─── Page Heading ─── --}}
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
            <h1>{{ $driver->full_name }}</h1>
            <p>
                @if($driver->code)
                    <code>{{ $driver->code }}</code>
                    @if($driver->position) · @endif
                @endif
                {{ $driver->position ?? '—' }}
            </p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('drivers.index') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
            <a href="{{ route('drivers.edit', $driver) }}" class="fleet-button fleet-button--primary">
                <i class="fas fa-pencil"></i> {{ __('messages.common.edit') }}
            </a>
        </div>
    </section>

    {{-- ─── Driver Info Panel ─── --}}
    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.drivers.details') }}</span>
                <h2>{{ __('messages.warehouse.basic_info') }}</h2>
            </div>
        </header>

        <div class="fleet-list">
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-id-badge"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.drivers.code') }}</strong>
                    <small>{{ $driver->code }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-hashtag"></i></span>
                <span class="fleet-list__content">
                    <strong>ID</strong>
                    <small>{{ $driver->id }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-user"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.drivers.first_name') }}</strong>
                    <small>{{ $driver->first_name }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-user"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.drivers.last_name') }}</strong>
                    <small>{{ $driver->last_name ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-phone"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.drivers.phone') }}</strong>
                    <small>{{ $driver->phone ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-briefcase"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.drivers.position') }}</strong>
                    <small>{{ $driver->position ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-circle-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.common.status') }}</strong>
                    <small>
                        @if($driver->is_active)
                            <span class="fleet-status fleet-status--success">
                                {{ __('messages.common.active') }}
                            </span>
                        @else
                            <span class="fleet-status fleet-status--muted">
                                {{ __('messages.common.inactive') }}
                            </span>
                        @endif
                    </small>
                </span>
            </div>

            @if($driver->notes)
                <div class="fleet-list__item">
                    <span class="fleet-list__icon"><i class="fas fa-comment"></i></span>
                    <span class="fleet-list__content">
                        <strong>{{ __('messages.common.notes') }}</strong>
                        <small>{{ $driver->notes }}</small>
                    </span>
                </div>
            @endif

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-plus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.created') }}</strong>
                    <small>{{ $driver->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.last_updated') }}</strong>
                    <small>{{ $driver->updated_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>
        </div>
    </section>
</div>
@endsection