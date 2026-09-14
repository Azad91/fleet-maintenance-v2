@extends('layouts.app')

@section('title', __('messages.super_admin.companies.create_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.super_admin.companies.create_title') }}</h1>
    </div>
    <a href="{{ route('super-admin.companies.index') }}" class="btn btn-secondary">
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
        <form action="{{ route('super-admin.companies.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name') }}" required autofocus
                           placeholder="{{ __('messages.super_admin.companies.name_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label fw-bold">{{ __('messages.super_admin.companies.slug') }}</label>
                    <input type="text" class="form-control" id="slug" name="slug"
                           value="{{ old('slug') }}"
                           placeholder="e.g.: bakubus"
                           pattern="[a-z0-9-]+">
                    <small class="text-muted">{{ __('messages.super_admin.companies.slug_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold">{{ __('messages.super_admin.companies.email') }}</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="info@company.com">
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label fw-bold">{{ __('messages.super_admin.companies.phone') }}</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="{{ old('phone') }}"
                           placeholder="+994 ...">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label fw-bold">{{ __('messages.super_admin.companies.address') }}</label>
                    <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                </div>

                {{-- ─── COMPANY DIRECTOR (required) ─── --}}
                <div class="col-12">
                    <hr class="my-2">
                    <h5 class="mb-1">
                        <i class="fas fa-user-tie"></i>
                        {{ __('messages.super_admin.companies.director_section') }}
                    </h5>
                    <p class="text-muted small mb-3">
                        {{ __('messages.super_admin.companies.director_section_hint') }}
                    </p>
                </div>

                <div class="col-md-6">
                    <label for="director_name" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.director_name') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="director_name" name="director_name"
                        value="{{ old('director_name') }}" required
                        placeholder="{{ __('messages.super_admin.companies.director_name_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="director_email" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.director_email') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="email" class="form-control" id="director_email" name="director_email"
                        value="{{ old('director_email') }}" required
                        placeholder="director@company.com">
                </div>

                <div class="col-md-6">
                    <label for="director_password" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.director_password') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="director_password"
                        name="director_password" required autocomplete="new-password">
                    <small class="text-muted">
                        {{ __('messages.super_admin.companies.director_password_hint') }}
                    </small>
                </div>

                <div class="col-md-6">
                    <label for="director_password_confirmation" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.director_password_confirm') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="director_password_confirmation"
                        name="director_password_confirmation" required autocomplete="new-password">
                </div>

                <div class="col-md-6">
                    <label for="director_pin" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.director_pin') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="director_pin" name="director_pin"
                        required maxlength="6" pattern="\d{4,6}"
                        value="{{ old('director_pin') }}" placeholder="1234">
                    <small class="text-muted">
                        {{ __('messages.super_admin.companies.director_pin_hint') }}
                    </small>
                </div>

                <div class="col-md-6">
                    <label for="director_pin_confirmation" class="form-label fw-bold">
                        {{ __('messages.super_admin.companies.director_pin_confirm') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="director_pin_confirmation"
                        name="director_pin_confirmation" required maxlength="6" pattern="\d{4,6}">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">
                            {{ __('messages.super_admin.companies.is_active') }}
                        </label>
                    </div>
                    <small class="text-muted">{{ __('messages.super_admin.companies.is_active_hint') }}</small>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('super-admin.companies.index') }}" class="btn btn-secondary">
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
