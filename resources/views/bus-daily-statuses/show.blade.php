@extends('layouts.app')

@section('title', __('messages.daily_status.details'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📄 {{ __('messages.daily_status.details') }}</h1>
        <a href="{{ route('bus-daily-statuses.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">🚌 {{ __('messages.daily_status.bus') }}</small>
                        <strong>{{ $status->bus->dqn ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.buses.route_number') }}</small>
                        <strong>{{ $status->bus->route_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📅 {{ __('messages.daily_status.date') }}</small>
                        <strong>{{ $status->date ? \Carbon\Carbon::parse($status->date)->format('d.m.Y') : '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📌 {{ __('messages.daily_status.status') }}</small>
                        <strong>{{ $status->status }}</strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📝 {{ __('messages.daily_status.notes') }}</small>
                        <strong>{{ $status->notes ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection