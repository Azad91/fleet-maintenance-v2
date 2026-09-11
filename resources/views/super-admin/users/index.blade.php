@extends('layouts.app')

@section('title', __('messages.super_admin.users.title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-1">{{ __('messages.super_admin.users.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.super_admin.users.subtitle') }}</p>
    </div>
    <a href="{{ route('super-admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('messages.super_admin.users.new') }}
    </a>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{!! session('success') !!}
    </div>
@endif

@if(session('error'))
    <div class="fleet-alert fleet-alert--error">
        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
    </div>
@endif

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('super-admin.users.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label fw-bold">{{ __('messages.common.search') }}</label>
                <input type="text" name="search" id="search" class="form-control"
                       value="{{ request('search') }}"
                       placeholder="{{ __('messages.super_admin.users.search_placeholder') }}">
            </div>
            <div class="col-md-2">
                <label for="role" class="form-label fw-bold">{{ __('messages.super_admin.users.filter_role') }}</label>
                <select name="role" id="role" class="form-select">
                    <option value="">{{ __('messages.super_admin.users.all_roles') }}</option>
                    <option value="super_admin" @selected(request('role') === 'super_admin')>{{ __('messages.super_admin.users.role_super_admin') }}</option>
                    <option value="user" @selected(request('role') === 'user')>{{ __('messages.super_admin.users.role_user') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label fw-bold">{{ __('messages.super_admin.users.filter_status') }}</label>
                <select name="status" id="status" class="form-select">
                    <option value="">{{ __('messages.super_admin.users.all_statuses') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('messages.super_admin.users.status_active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.super_admin.users.status_inactive') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> {{ __('messages.common.filter') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.super_admin.users.name') }}</th>
                        <th>{{ __('messages.super_admin.users.email') }}</th>
                        <th>{{ __('messages.super_admin.users.employee_code') }}</th>
                        <th>{{ __('messages.super_admin.users.role') }}</th>
                        <th class="text-center">{{ __('messages.super_admin.users.garages_count') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if($user->is(auth()->user()))
                                    <span class="badge text-bg-primary ms-1">{{ __('messages.users.you') }}</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->employee_code)
                                    <code>{{ $user->employee_code }}</code>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($user->isSuperAdmin())
                                    <span class="badge bg-danger">{{ __('messages.super_admin.users.role_super_admin') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.super_admin.users.role_user') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $user->garages_count }}</span>
                            </td>
                            <td>
                                @if($user->is_active ?? true)
                                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('super-admin.users.edit', $user) }}" class="btn btn-sm btn-outline-warning" title="{{ __('messages.common.edit') }}">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    @if(!$user->is(auth()->user()) && !$user->isSuperAdmin())
                                        <form action="{{ route('super-admin.users.destroy', $user) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.super_admin.users.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.common.delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-users fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.common.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $users->withQueryString()->links() }}
</div>
@endsection
