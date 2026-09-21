@extends('layouts.guest')

@section('title', __('auth.verify_title') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-envelope-check-fill"></i>
        </div>
        <div class="auth-title">{{ __('auth.verify_title') }}</div>
        <p class="auth-subtitle">{{ __('auth.verify_hint') }}</p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ __('auth.verify_sent') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-login">
                <i class="bi bi-send me-2"></i> {{ __('auth.verify_resend') }}
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
