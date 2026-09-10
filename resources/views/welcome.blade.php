@extends('layouts.guest')

@section('title', __('messages.welcome.title_plain'))

@section('content')
<div class="welcome-container">
    <div class="card-welcome">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <h1>{!! __('messages.welcome.title') !!}</h1>
        <p class="subtitle">{{ __('messages.welcome.subtitle') }}</p>

        <div class="features">
            <div class="feature-item">
                <i class="bi bi-bus-front"></i>
                <h6>{{ __('messages.welcome.buses_title') }}</h6>
                <p>{{ __('messages.welcome.buses_desc') }}</p>
            </div>
            <div class="feature-item">
                <i class="bi bi-clipboard"></i>
                <h6>{{ __('messages.welcome.complaints_title') }}</h6>
                <p>{{ __('messages.welcome.complaints_desc') }}</p>
            </div>
            <div class="feature-item">
                <i class="bi bi-box-seam"></i>
                <h6>{{ __('messages.welcome.warehouse_title') }}</h6>
                <p>{{ __('messages.welcome.warehouse_desc') }}</p>
            </div>
        </div>

        <div class="btn-group-custom">
            <a href="{{ route('login') }}" class="btn-custom btn-login">
                <i class="bi bi-box-arrow-in-right"></i> {{ __('messages.auth.sign_in') }}
            </a>
        </div>

        <div class="footer-text">
            {!! __('messages.welcome.copyright', ['year' => date('Y')]) !!}
        </div>
    </div>
</div>
@endsection