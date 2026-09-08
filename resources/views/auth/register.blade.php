@extends('layouts.guest')

@section('title', 'Register - Fleet Maintenance')

@section('styles')
    {{-- No extra CSS needed, everything is in app.css --}}
@endsection

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <div class="auth-title">Register</div>
        <p class="auth-subtitle">Create a new account</p>

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
                <label for="name">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required placeholder="Full Name">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required placeholder="your@email.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="form-control" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus me-2"></i> Register
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="{{ route('login') }}">Sign In</a>
        </div>
    </div>
</div>
@endsection