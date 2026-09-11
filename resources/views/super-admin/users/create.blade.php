@extends('layouts.app')

@section('title', __('messages.super_admin.users.create_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.super_admin.users.create_title') }}</h1>
    </div>
    <a href="{{ route('super-admin.users.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

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

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> {{ __('messages.super_admin.users.create_hint') }}
        </div>

        <form action="{{ route('super-admin.users.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">
                        {{ __('messages.super_admin.users.name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name') }}" required autofocus
                           placeholder="{{ __('messages.super_admin.users.name_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold">
                        {{ __('messages.super_admin.users.email') }} <span class="text-danger">*</span>
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="{{ old('email') }}" required
                           placeholder="user@example.com">
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label fw-bold">
                        {{ __('messages.super_admin.users.password') }} <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required
                           autocomplete="new-password">
                    <small class="text-muted">{{ __('messages.super_admin.users.password_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label fw-bold">
                        {{ __('messages.super_admin.users.password_confirm') }} <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required
                           autocomplete="new-password">
                </div>

                <div class="col-md-6">
                    <label for="employee_code" class="form-label fw-bold">{{ __('messages.super_admin.users.employee_code') }}</label>
                    <input type="text" class="form-control" id="employee_code" name="employee_code"
                           value="{{ old('employee_code') }}"
                           placeholder="{{ __('messages.super_admin.users.employee_code_placeholder') }}">
                    <small class="text-muted">{{ __('messages.super_admin.users.employee_code_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label for="pin" class="form-label fw-bold">{{ __('messages.super_admin.users.pin') }}</label>
                    <input type="text" class="form-control" id="pin" name="pin"
                           value="{{ old('pin') }}"
                           placeholder="1234"
                           maxlength="6"
                           pattern="\d{4,6}">
                    <small class="text-muted">{{ __('messages.super_admin.users.pin_hint') }}</small>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">
                            {{ __('messages.super_admin.users.is_active') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('super-admin.users.index') }}" class="btn btn-secondary">
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
