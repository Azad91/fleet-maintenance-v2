@extends('layouts.app')

@section('title', __('messages.super_admin.garages.create_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.super_admin.garages.create_title') }}</h1>
    </div>
    <a href="{{ route('super-admin.garages.index') }}" class="btn btn-secondary">
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
        <form action="{{ route('super-admin.garages.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="company_id" class="form-label fw-bold">
                        {{ __('messages.super_admin.garages.company') }} <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="company_id" name="company_id" required autofocus>
                        <option value="">{{ __('messages.super_admin.garages.select_company') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}"
                                @selected(old('company_id', $selectedCompanyId) == $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="code" class="form-label fw-bold">
                        {{ __('messages.super_admin.garages.code') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="code" name="code"
                           value="{{ old('code') }}" required
                           placeholder="{{ __('messages.super_admin.garages.code_placeholder') }}">
                    <small class="text-muted">{{ __('messages.super_admin.garages.code_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">
                        {{ __('messages.super_admin.garages.name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name') }}" required
                           placeholder="{{ __('messages.super_admin.garages.name_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label fw-bold">{{ __('messages.super_admin.garages.phone') }}</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="{{ old('phone') }}"
                           placeholder="+994 ...">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label fw-bold">{{ __('messages.super_admin.garages.address') }}</label>
                    <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">
                            {{ __('messages.super_admin.garages.is_active') }}
                        </label>
                    </div>
                    <small class="text-muted">{{ __('messages.super_admin.garages.is_active_hint') }}</small>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('super-admin.garages.index') }}" class="btn btn-secondary">
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
