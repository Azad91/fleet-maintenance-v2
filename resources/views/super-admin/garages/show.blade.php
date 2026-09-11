@extends('layouts.app')

@section('title', $garage->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ $garage->name }}</h1>
        <p class="text-muted mb-0">
            <code>{{ $garage->code }}</code>
            @if($garage->company)
                · <a href="{{ route('super-admin.companies.show', $garage->company) }}" class="text-decoration-none">
                    {{ $garage->company->name }}
                </a>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('super-admin.garages.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
        <a href="{{ route('super-admin.garages.edit', $garage) }}" class="btn btn-warning">
            <i class="fas fa-pencil"></i> {{ __('messages.common.edit') }}
        </a>
    </div>
</div>

{{-- Garage Info --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">{{ __('messages.super_admin.garages.show_title') }}</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.super_admin.garages.phone') }}</small>
                <strong>{{ $garage->phone ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.common.status') }}</small>
                @if($garage->is_active)
                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                @else
                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                @endif
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.super_admin.garages.users_count') }}</small>
                <strong>{{ $garage->users->count() }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.complaints.created') }}</small>
                <strong>{{ $garage->created_at->format('d.m.Y H:i') }}</strong>
            </div>
            @if($garage->address)
                <div class="col-12">
                    <small class="text-muted d-block">{{ __('messages.super_admin.garages.address') }}</small>
                    <strong>{{ $garage->address }}</strong>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Users --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('messages.super_admin.garages.admins_title') }} ({{ $garage->users->count() }})</h5>
    </div>
    <div class="card-body p-0">
        @if($garage->users->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.users.table_user') }}</th>
                            <th>{{ __('messages.users.table_email') }}</th>
                            <th>{{ __('messages.users.table_role') }}</th>
                            <th>{{ __('messages.users.table_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($garage->users as $user)
                            <tr>
                                <td><strong>{{ $user->name }}</strong></td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ __('roles.' . $user->pivot->role) }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->pivot->is_active)
                                        <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-5">{{ __('messages.super_admin.garages.no_users') }}</div>
        @endif
    </div>
</div>
@endsection
