@extends('layouts.app')

@section('title', __('messages.drivers.details'))

@section('content')
<div class="container">
    <h1>🧑‍✈️ {{ __('messages.drivers.details') }}</h1>

    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.drivers.code') }}</small>
                        <strong>{{ $driver->code }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.drivers.first_name') }}</small>
                        <strong>{{ $driver->first_name }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.drivers.last_name') }}</small>
                        <strong>{{ $driver->last_name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.drivers.phone') }}</small>
                        <strong>{{ $driver->phone ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.drivers.position') }}</small>
                        <strong>{{ $driver->position ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.common.status') }}</small>
                        <strong>
                            <span class="badge-status {{ $driver->is_active ? 'aktiv' : 'passiv' }}">
                                {{ $driver->is_active ? '✅ ' . __('messages.common.active') : '❌ ' . __('messages.common.inactive') }}
                            </span>
                        </strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📝 {{ __('messages.common.notes') }}</small>
                        <strong>{{ $driver->notes ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">⬅ {{ __('messages.common.back') }}</a>
    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-warning">✏️ {{ __('messages.common.edit') }}</a>
</div>
@endsection