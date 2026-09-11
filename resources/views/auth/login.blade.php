@extends('layouts.guest')

@section('title', __('messages.auth.login') . ' · Fleet Control')

@section('content')
<div class="auth-container">
    <div class="card-auth">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <div class="auth-title">{{ __('messages.auth.login') }}</div>
        <p class="auth-subtitle">{{ __('messages.auth.sign_in_subtitle') }}</p>

        @if ($errors->any())
            <div class="alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4" id="loginTabs" role="tablist">
            <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link w-100 {{ $errors->has('employee_code') || $errors->has('pin') ? '' : 'active' }}"
                        id="email-tab" data-bs-toggle="tab" data-bs-target="#email-pane" type="button" role="tab">
                    <i class="bi bi-envelope"></i> {{ __('messages.auth.tab_email') }}
                </button>
            </li>
            <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link w-100 {{ $errors->has('employee_code') || $errors->has('pin') ? 'active' : '' }}"
                        id="pin-tab" data-bs-toggle="tab" data-bs-target="#pin-pane" type="button" role="tab">
                    <i class="bi bi-shield-lock"></i> {{ __('messages.auth.tab_pin') }}
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Email + Password --}}
            <div class="tab-pane fade {{ $errors->has('employee_code') || $errors->has('pin') ? '' : 'show active' }}"
                 id="email-pane" role="tabpanel">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email">{{ __('messages.auth.email') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" class="form-control" name="email"
                                   value="{{ old('email') }}" autocomplete="username"
                                   placeholder="{{ __('messages.auth.email_placeholder') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">{{ __('messages.auth.password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" class="form-control" name="password"
                                   autocomplete="current-password" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="form-check d-flex justify-content-between align-items-center">
                        <div>
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">{{ __('messages.auth.remember_me') }}</label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="auth-forgot-link">{{ __('messages.auth.forgot_password') }}</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i> {{ __('messages.auth.sign_in') }}
                    </button>
                </form>
            </div>

            {{-- Employee Code + PIN --}}
            <div class="tab-pane fade {{ $errors->has('employee_code') || $errors->has('pin') ? 'show active' : '' }}"
                 id="pin-pane" role="tabpanel">
                <form method="POST" action="{{ route('login.pin') }}">
                    @csrf

                    <div class="form-group">
                        <label for="employee_code">{{ __('messages.auth.employee_code') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <input id="employee_code" type="text" class="form-control" name="employee_code"
                                   value="{{ old('employee_code') }}"
                                   autocomplete="username"
                                   placeholder="{{ __('messages.auth.employee_code_placeholder') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pin">{{ __('messages.auth.pin') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input id="pin" type="password" class="form-control" name="pin"
                                   inputmode="numeric"
                                   pattern="\d{4,6}"
                                   maxlength="6"
                                   autocomplete="off"
                                   placeholder="••••">
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i> {{ __('messages.auth.sign_in') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="auth-footer">{{ __('messages.auth.new_users_note') }}</div>
    </div>
</div>
@endsection
