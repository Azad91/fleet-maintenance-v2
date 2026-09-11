@extends('layouts.app')

@section('title', __('messages.super_admin.users.edit_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.super_admin.users.edit_title') }}</h1>
        <p class="text-muted mb-0">{{ $user->name }}</p>
    </div>
    <a href="{{ route('super-admin.users.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

@if(session('error'))
    <div class="fleet-alert fleet-alert--error">
        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ __('messages.users.not_saved') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('super-admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-bold">
                                {{ __('messages.super_admin.users.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name', $user->name) }}" required autofocus>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">
                                {{ __('messages.super_admin.users.email') }} <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label fw-bold">{{ __('messages.super_admin.users.new_password') }}</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   autocomplete="new-password">
                            <small class="text-muted">{{ __('messages.super_admin.users.password_optional') }}</small>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-bold">{{ __('messages.super_admin.users.password_confirm') }}</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                                   autocomplete="new-password">
                        </div>

                        <div class="col-md-6">
                            <label for="employee_code" class="form-label fw-bold">{{ __('messages.super_admin.users.employee_code') }}</label>
                            <input type="text" class="form-control" id="employee_code" name="employee_code"
                                   value="{{ old('employee_code', $user->employee_code) }}">
                        </div>

                        <div class="col-md-6">
                            <label for="pin" class="form-label fw-bold">{{ __('messages.super_admin.users.pin') }}</label>
                            <input type="text" class="form-control" id="pin" name="pin"
                                   value="{{ old('pin') }}"
                                   placeholder="1234"
                                   maxlength="6"
                                   pattern="\d{4,6}">
                            <small class="text-muted">{{ __('messages.super_admin.users.pin_change_hint') }}</small>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                       @checked(old('is_active', $user->is_active ?? true))>
                                <label class="form-check-label" for="is_active">
                                    {{ __('messages.super_admin.users.is_active') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('messages.common.update') }}
                        </button>
                        <a href="{{ route('super-admin.users.index') }}" class="btn btn-secondary">
                            {{ __('messages.common.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Garages sidebar --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.super_admin.users.garages_title') }} ({{ $user->garages->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($user->garages->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach($user->garages as $garage)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $garage->name }}</strong>
                                        <small class="d-block text-muted">{{ $garage->company->name ?? '—' }}</small>
                                    </div>
                                    <span class="badge bg-secondary">{{ __('roles.' . $garage->pivot->role) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-muted py-4">{{ __('messages.super_admin.users.no_garages') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
