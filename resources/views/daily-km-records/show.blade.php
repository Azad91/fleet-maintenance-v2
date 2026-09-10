@extends('layouts.app')

@section('title', __('messages.daily_km.details'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📄 {{ __('messages.daily_km.details') }}</h1>
        <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">🚌 {{ __('messages.daily_km.bus') }}</small>
                        <strong>{{ $record->bus->dqn ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">{{ __('messages.buses.route_number') }}</small>
                        <strong>{{ $record->bus->route_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📅 {{ __('messages.daily_km.date') }}</small>
                        <strong>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📊 {{ __('messages.daily_km.km') }}</small>
                        <strong>{{ number_format($record->km, 0, ',', '.') }} km</strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📝 {{ __('messages.daily_km.notes') }}</small>
                        <strong>{{ $record->notes ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title mt-4">
        📊 {{ __('messages.daily_km.history_for', ['dqn' => $record->bus->dqn]) }}
        <span class="badge bg-primary ms-2">{{ __('messages.daily_km.history_count', ['count' => $history->count()]) }}</span>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>📅 {{ __('messages.daily_km.date') }}</th>
                            <th>📊 {{ __('messages.daily_km.km') }}</th>
                            <th>📝 {{ __('messages.daily_km.notes') }}</th>
                            <th>{{ __('messages.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $index => $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('d.m.Y') : '-' }}</td>
                            <td><strong>{{ number_format($item->km, 0, ',', '.') }} km</strong></td>
                            <td>{{ $item->notes ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('daily-km-records.show', $item) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('daily-km-records.edit', $item) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                {{ __('messages.daily_km.no_records_for_bus') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('daily-km-records.create') }}?bus_id={{ $record->bus_id }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> {{ __('messages.daily_km.add_for_bus') }}
        </a>
    </div>
</div>
@endsection