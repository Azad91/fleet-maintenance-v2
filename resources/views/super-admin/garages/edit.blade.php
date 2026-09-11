@extends('layouts.app')

@section('title', __('messages.super_admin.garages.edit_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.super_admin.garages.edit_title') }}</h1>
        <p class="text-muted mb-0"><code>{{ $garage->code }}</code></p>
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
        <form action="{{ route('super-admin.garages.update', $garage) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="company_id" class="form-label fw-bold">
                        {{ __('messages.super_admin.garages.company') }} <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="company_id" name="company_id" required autofocus>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id', $garage->company_id) == $company->id)>
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
                           value="{{ old('code', $garage->code) }}" required>
                    <small class="text-muted">{{ __('messages.super_admin.garages.code_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">
                        {{ __('messages.super_admin.garages.name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name', $garage->name) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label fw-bold">{{ __('messages.super_admin.garages.phone') }}</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="{{ old('phone', $garage->phone) }}">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label fw-bold">{{ __('messages.super_admin.garages.address') }}</label>
                    <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $garage->address) }}</textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $garage->is_active))>
                        <label class="form-check-label" for="is_active">
                            {{ __('messages.super_admin.garages.is_active') }}
                        </label>
                    </div>
                    <small class="text-muted">{{ __('messages.super_admin.garages.is_active_hint') }}</small>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('messages.common.update') }}
                </button>
                <a href="{{ route('super-admin.garages.index') }}" class="btn btn-secondary">
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
