@extends('layouts.app')

@section('title', __('messages.employees.details'))

@section('content')
<div class="fleet-dashboard">
    {{-- ─── Page Heading ─── --}}
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
            <h1>{{ $employee->full_name }}</h1>
            <p>
                @if($employee->code)
                    <code>{{ $employee->code }}</code> ·
                @endif
                {{ $employee->position ?? '—' }}
            </p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('employees.index') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
            <a href="{{ route('employees.edit', $employee) }}" class="fleet-button fleet-button--primary">
                <i class="fas fa-pencil"></i> {{ __('messages.common.edit') }}
            </a>
        </div>
    </section>

    {{-- ─── Employee Info Panel ─── --}}
    <section class="fleet-panel mb-4">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.employees.details') }}</span>
                <h2>{{ __('messages.warehouse.basic_info') }}</h2>
            </div>
        </header>

        <div class="fleet-list">
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-id-badge"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.employees.code') }}</strong>
                    <small>{{ $employee->code ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-hashtag"></i></span>
                <span class="fleet-list__content">
                    <strong>ID</strong>
                    <small>{{ $employee->id }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-user"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.employees.first_name') }}</strong>
                    <small>{{ $employee->first_name }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-user"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.employees.last_name') }}</strong>
                    <small>{{ $employee->last_name }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-briefcase"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.employees.position') }}</strong>
                    <small>{{ $employee->position }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-circle-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.common.status') }}</strong>
                    <small>
                        @if($employee->is_active)
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

            @if($employee->notes)
                <div class="fleet-list__item">
                    <span class="fleet-list__icon"><i class="fas fa-comment"></i></span>
                    <span class="fleet-list__content">
                        <strong>{{ __('messages.common.notes') }}</strong>
                        <small>{{ $employee->notes }}</small>
                    </span>
                </div>
            @endif

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-plus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.created') }}</strong>
                    <small>{{ $employee->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.last_updated') }}</strong>
                    <small>{{ $employee->updated_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>
        </div>
    </section>
</div>
@endsection