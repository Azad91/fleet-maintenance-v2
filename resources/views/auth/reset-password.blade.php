@extends('layouts.guest')

@section('title', __('Şifrəni sıfırla') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div class="auth-title">{{ __('Şifrəni sıfırla') }}</div>
        <p class="auth-subtitle">{{ __('Yeni şifrənizi daxil edin.') }}</p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="form-group">
                <label for="email">{{ __('messages.auth.email') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="form-control" name="email"
                           value="{{ old('email', $request->email) }}"
                           required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">{{ __('messages.profile.new_password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password"
                           required autocomplete="new-password">
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">{{ __('messages.profile.confirm_password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input id="password_confirmation" type="password" class="form-control"
                           name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-check-circle me-2"></i> {{ __('Şifrəni yenilə') }}
            </button>
        </form>
    </div>
</div>
@endsection
