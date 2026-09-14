@extends('layouts.guest')

@section('title', __('Şifrəni təsdiqlə') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="auth-title">{{ __('Şifrəni təsdiqlə') }}</div>
        <p class="auth-subtitle">
            {{ __('Davam etməzdən əvvəl şifrənizi təsdiqləyin.') }}
        </p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="form-group">
                <label for="password">{{ __('messages.profile.current_password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password"
                           required autocomplete="current-password" autofocus>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-check-circle me-2"></i> {{ __('Təsdiqlə') }}
            </button>
        </form>
    </div>
</div>
@endsection
