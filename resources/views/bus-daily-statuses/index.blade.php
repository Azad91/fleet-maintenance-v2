@extends('layouts.app')

@section('title', __('messages.daily_status.title'))

@php
    use App\Enums\RoleEnum;

    // Admin + Daily Status Manager/Worker can manage records
    $canManageDailyStatus = auth()->user()?->isSuperAdmin()
        || auth()->user()?->hasGarageRole(array_merge([RoleEnum::ADMIN->value], RoleEnum::dailyStatusRoles()));
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>📋 {{ __('messages.daily_status.title') }}</h1>
    @if($canManageDailyStatus)
    <div>
        <a href="{{ route('bus-daily-statuses.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> {{ __('messages.daily_status.import') }}
        </a>
        <a href="{{ route('bus-daily-statuses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('messages.daily_status.new') }}
        </a>
    </div>
    @endif
</div>

{{-- ─── Filter panel ─── --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('bus-daily-statuses.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date" class="form-label fw-bold">
                    <i class="bi bi-calendar-event"></i> {{ __('messages.daily_status.date') }}
                </label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $date }}">
                <small class="text-muted d-block mt-1">{{ __('messages.daily_status.filter_date_hint') }}</small>
            </div>
            <div class="col-md-3">
                <label for="dqn" class="form-label fw-bold">
                    <i class="bi bi-bus-front"></i> {{ __('messages.buses.dqn') }}
                </label>
                <input type="text" name="dqn" id="dqn" class="form-control"
                       value="{{ $dqn }}"
                       placeholder="{{ __('messages.buses.filter_dqn') }}"
                       autocomplete="off"
                       style="text-transform: uppercase;">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label fw-bold">
                    <i class="bi bi-flag"></i> {{ __('messages.daily_status.status') }}
                </label>
                <select name="status" id="status" class="form-select">
                    <option value="">{{ __('messages.common.select') }}</option>
                    @foreach($availableStatuses as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search"></i> {{ __('messages.common.filter') }}
                </button>
                <a href="{{ route('bus-daily-statuses.index') }}" class="btn btn-secondary" title="{{ __('messages.common.reset') }}">
                    <i class="bi bi-x-circle"></i>
                </a>
                <a href="{{ route('bus-daily-statuses.export', ['date' => $date, 'dqn' => $dqn, 'status' => $status]) }}"
                   class="btn btn-outline-success"
                   title="{{ __('messages.common.export') }}">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.daily_status.bus') }} (DQN)</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.daily_status.date') }}</th>
                        <th>{{ __('messages.daily_status.status') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statuses as $status_row)
                    <tr>
                        <td>{{ $statuses->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $status_row->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $status_row->bus->route_number ?? '-' }}</td>
                        <td>{{ $status_row->date ? \Carbon\Carbon::parse($status_row->date)->format('d.m.Y') : '-' }}</td>
                        <td>
                            <span class="badge bg-secondary text-white" style="font-size: 14px; padding: 8px 14px; border-radius: 6px;">
                                {{ $status_row->status }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('bus-daily-statuses.show', $status_row) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $status_row)
                                    <a href="{{ route('bus-daily-statuses.edit', $status_row) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-calendar2-week" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            {{ __('messages.daily_status.no_records') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($statuses->total() > 0)
            <div class="text-center text-muted small mt-2 mb-3">
                {{ __('messages.common.showing', [
                    'from'  => $statuses->firstItem(),
                    'to'    => $statuses->lastItem(),
                    'total' => $statuses->total(),
                ]) }}
            </div>
        @endif
    </div>
</div>

<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $statuses->withQueryString()->links() }}
</div>
@endsection