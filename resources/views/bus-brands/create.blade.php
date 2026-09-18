@extends('layouts.app')

@section('title', __('messages.bus_brands.create_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
        <h1 class="mb-0">{{ __('messages.bus_brands.create_title') }}</h1>
    </div>
    <a href="{{ route('bus-brands.index') }}" class="btn btn-secondary">
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
        <form action="{{ route('bus-brands.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold">
                        {{ __('messages.bus_brands.name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name') }}" required autofocus
                           placeholder="{{ __('messages.bus_brands.name_placeholder') }}">
                </div>

                <div class="col-md-6">
                    <label for="code" class="form-label fw-bold">
                        {{ __('messages.bus_brands.code') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="code" name="code"
                           value="{{ old('code') }}" required
                           placeholder="{{ __('messages.bus_brands.code_placeholder') }}"
                           style="text-transform: uppercase;">
                    <small class="text-muted">{{ __('messages.bus_brands.code_hint') }}</small>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">
                            {{ __('messages.bus_brands.is_active') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('bus-brands.index') }}" class="btn btn-secondary">
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
