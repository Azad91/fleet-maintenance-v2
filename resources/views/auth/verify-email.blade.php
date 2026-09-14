@extends('layouts.guest')

@section('title', __('Email təsdiqi') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-envelope-check-fill"></i>
        </div>
        <div class="auth-title">{{ __('Email təsdiqi') }}</div>
        <p class="auth-subtitle">
            {{ __('Davam etməzdən əvvəl email ünvanınızı təsdiqləyin. Link göndərildi.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ __('Yeni təsdiq linki email ünvanınıza göndərildi.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-login">
                <i class="bi bi-send me-2"></i> {{ __('Təsdiq linkini yenidən göndər') }}
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
