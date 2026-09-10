@extends('layouts.guest')

@section('title', __('messages.auth.register') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <div class="auth-title">{{ __('messages.auth.register') }}</div>
        <p class="auth-subtitle">{{ __('messages.auth.register_subtitle') }}</p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label for="name">{{ __('messages.auth.full_name') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">{{ __('messages.auth.email') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">{{ __('messages.auth.password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">{{ __('messages.auth.password_confirm') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
                </div>
            </div>

            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus me-2"></i> {{ __('messages.auth.register') }}
            </button>
        </form>

        <div class="auth-footer">
            {{ __('messages.auth.already_have_account') }} <a href="{{ route('login') }}">{{ __('messages.auth.sign_in') }}</a>
        </div>
    </div>
</div>
@endsection