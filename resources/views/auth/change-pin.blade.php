@extends('layouts.guest')

@section('title', __('messages.pin_change.title') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div class="auth-title">{{ __('messages.pin_change.title') }}</div>
        <p class="auth-subtitle">{{ __('messages.pin_change.subtitle') }}</p>

        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle"></i> {{ __('messages.pin_change.hint') }}
        </div>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('pin.change.update') }}">
            @csrf

            <div class="form-group">
                <label for="current_pin">{{ __('messages.pin_change.current_pin') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input id="current_pin" type="password" class="form-control" name="current_pin"
                           inputmode="numeric" pattern="\d{4,6}" maxlength="6" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="pin">{{ __('messages.pin_change.new_pin') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input id="pin" type="password" class="form-control" name="pin"
                           inputmode="numeric" pattern="\d{4,6}" maxlength="6" required>
                </div>
            </div>

            <div class="form-group">
                <label for="pin_confirmation">{{ __('messages.pin_change.confirm_pin') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input id="pin_confirmation" type="password" class="form-control" name="pin_confirmation"
                           inputmode="numeric" pattern="\d{4,6}" maxlength="6" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-check-circle me-2"></i> {{ __('messages.pin_change.submit') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn-register w-100">
                <i class="bi bi-box-arrow-left"></i> {{ __('messages.auth.logout') }}
            </button>
        </form>
    </div>
</div>
@endsection
