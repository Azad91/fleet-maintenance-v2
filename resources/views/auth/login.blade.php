@extends('layouts.guest')

@section('title', 'Daxil Ol - Fleet Maintenance')

@section('styles')
    {{-- Bu səhifəyə xüsusi CSS yoxdur, hamısı app.css - dən gəlir --}}
@endsection

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <div class="auth-title">Daxil <span>Ol</span></div>
        <p class="auth-subtitle">Hesabınıza daxil olun</p>

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
                <label for="password">Şifrə</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-check d-flex justify-content-between align-items-center">
                <div>
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Məni xatırla</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-forgot-link">Şifrəni unutdun?</a>
                @endif
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Daxil Ol
            </button>
        </form>

        <div class="auth-footer">
            Hesabın yoxdur? <a href="{{ route('register') }}">Qeydiyyat</a>
        </div>
    </div>
</div>
@endsection
