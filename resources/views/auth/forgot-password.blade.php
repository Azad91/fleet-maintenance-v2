@extends('layouts.guest')

@section('title', __('auth.forgot_title') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-envelope-fill"></i>
        </div>
        <div class="auth-title">{{ __('auth.forgot_title') }}</div>
        <p class="auth-subtitle">{{ __('auth.forgot_hint') }}</p>

        @if (session('status'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-group">
                <label for="email">{{ __('messages.auth.email') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="form-control" name="email"
                           value="{{ old('email') }}"
                           placeholder="{{ __('messages.auth.email_placeholder') }}"
                           required autofocus>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-send me-2"></i> {{ __('auth.forgot_button') }}
            </button>
        </form>

        <div class="auth-footer">
            <a href="{{ route('login') }}">
                <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
        </div>
    </div>
</div>
@endsection
