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
                    @forelse($statuses as $status)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $status->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $status->bus->route_number ?? '-' }}</td>
                        <td>{{ $status->date ? \Carbon\Carbon::parse($status->date)->format('d.m.Y') : '-' }}</td>
                        <td>
                            <span class="badge bg-secondary text-white" style="font-size: 14px; padding: 8px 14px; border-radius: 6px;">
                                {{ $status->status }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('bus-daily-statuses.show', $status) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canManageDailyStatus)
                                    <a href="{{ route('bus-daily-statuses.edit', $status) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
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
    </div>
</div>
<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $statuses->withQueryString()->links() }}
</div>
@endsection
