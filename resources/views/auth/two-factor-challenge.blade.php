@extends('layouts.guest')

@section('title', __('messages.two_factor.title') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div class="auth-title">{{ __('messages.two_factor.title') }}</div>
        <p class="auth-subtitle">{{ __('messages.two_factor.subtitle') }}</p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.challenge.store') }}">
            @csrf

            <div class="form-group">
                <label for="code">{{ __('messages.two_factor.code_label') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input id="code" type="text" class="form-control" name="code"
                           inputmode="text"
                           autocomplete="one-time-code"
                           autofocus required
                           placeholder="000000">
                </div>
                <small class="text-muted d-block mt-2">
                    {{ __('messages.two_factor.code_hint') }}
                </small>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-check-circle me-2"></i> {{ __('messages.two_factor.verify') }}
            </button>
        </form>

        <form method="POST" action="{{ route('two-factor.challenge.cancel') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn-register w-100">
                <i class="bi bi-arrow-left"></i> {{ __('messages.two_factor.cancel') }}
            </button>
        </form>
    </div>
</div>
@endsection
