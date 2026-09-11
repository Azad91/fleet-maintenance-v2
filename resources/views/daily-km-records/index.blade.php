@extends('layouts.app')

@section('title', __('messages.daily_km.title'))

@php
    use App\Enums\RoleEnum;

    // Admin + Daily KM Manager/Worker can manage records
    $canManageDailyKm = auth()->user()?->isSuperAdmin()
        || auth()->user()?->hasGarageRole(array_merge([RoleEnum::ADMIN->value], RoleEnum::dailyKmRoles()));
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>📊 {{ __('messages.daily_km.title') }}</h1>
    @if($canManageDailyKm)
    <div>
        <a href="{{ route('daily-km-records.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> {{ __('messages.daily_km.import') }}
        </a>
        <a href="{{ route('daily-km-records.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('messages.daily_km.new') }}
        </a>
    </div>
    @endif
</div>

<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="search"
               placeholder="{{ __('messages.daily_km.search_placeholder') }}"
               value="{{ request('search') }}">
        <button class="btn btn-primary"><i class="bi bi-search"></i> {{ __('messages.common.search') }}</button>
        <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">{{ __('messages.common.reset') }}</a>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.daily_km.bus') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.daily_km.date') }}</th>
                        <th>{{ __('messages.daily_km.km') }}</th>
                        <th>{{ __('messages.daily_km.notes') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $record->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $record->bus->route_number ?? '-' }}</td>
                        <td>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '-' }}</td>
                        <td><strong>{{ number_format($record->km, 0, ',', '.') }} km</strong></td>
                        <td>{{ $record->notes ?? '-' }}</td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('daily-km-records.show', $record) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canManageDailyKm)
                                    <a href="{{ route('daily-km-records.edit', $record) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasGarageRole(array_merge([RoleEnum::ADMIN->value], [RoleEnum::DAILY_KM_MANAGER->value])))
                                        <form action="{{ route('daily-km-records.destroy', $record) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.common.confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-graph-up" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            {{ __('messages.daily_km.no_records') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
