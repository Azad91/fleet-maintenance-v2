@extends('layouts.app')

@section('title', __('messages.two_factor.setup_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.two_factor.setup_title') }}</h1>
    </div>
</div>

@if(session('warning'))
    <div class="fleet-alert fleet-alert--warning">
        <i class="fas fa-exclamation-triangle"></i>{{ session('warning') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ __('messages.two_factor.invalid_code') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-qrcode"></i> {{ __('messages.two_factor.step_1_title') }}
                </h5>
            </div>
            <div class="card-body text-center">
                <p class="text-muted mb-3">{{ __('messages.two_factor.step_1_hint') }}</p>
                <img src="{{ $qrCodeUri }}" alt="QR Code" class="img-fluid" style="max-width: 220px;">
                <div class="mt-3">
                    <small class="text-muted">{{ __('messages.two_factor.manual_entry') }}</small>
                    <div class="mt-2">
                        <code style="font-size: 15px; letter-spacing: 1px;">{{ $secret }}</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle"></i> {{ __('messages.two_factor.step_2_title') }}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">{{ __('messages.two_factor.step_2_hint') }}</p>

                <form method="POST" action="{{ route('super-admin.security.2fa.confirm') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="code" class="form-label fw-bold">
                            {{ __('messages.two_factor.code_label') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input id="code" type="text" name="code"
                               class="form-control form-control-lg text-center"
                               inputmode="numeric" maxlength="6" pattern="\d{6}"
                               autofocus required autocomplete="one-time-code"
                               placeholder="000000"
                               style="letter-spacing: 6px; font-size: 22px;">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-shield-halved"></i> {{ __('messages.two_factor.enable') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
