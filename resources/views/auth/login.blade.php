@extends('layouts.guest')

@section('title', __('messages.auth.login') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <div class="auth-title">{{ __('messages.auth.login') }}</div>
        <p class="auth-subtitle">{{ __('messages.auth.sign_in_subtitle') }}</p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">{{ __('messages.auth.email') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="form-control" name="email"
                           value="{{ old('email') }}" required autofocus
                           placeholder="{{ __('messages.auth.email_placeholder') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="password">{{ __('messages.auth.password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password"
                           required placeholder="••••••••">
                </div>
            </div>

            <div class="form-check d-flex justify-content-between align-items-center">
                <div>
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">{{ __('messages.auth.remember_me') }}</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-forgot-link">{{ __('messages.auth.forgot_password') }}</a>
                @endif
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> {{ __('messages.auth.sign_in') }}
            </button>
        </form>

        <div class="auth-footer">{{ __('messages.auth.new_users_note') }}</div>
    </div>
</div>
@endsection