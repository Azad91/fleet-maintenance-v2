@extends('layouts.guest')

@section('title', 'Login - Fleet Maintenance')

@section('styles')
    {{-- No extra CSS needed, everything is in app.css --}}
@endsection

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <div class="auth-title">Login</div>
        <p class="auth-subtitle">Sign in to your account</p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus placeholder="your@email.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-check d-flex justify-content-between align-items-center">
                <div>
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-forgot-link">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="auth-footer">New users can only be created by an administrator.</div>
    </div>
</div>
@endsection