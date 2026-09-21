@extends('layouts.guest')

@section('title', __('messages.flash.no_garage_assigned'))

@section('content')
<div class="auth-container">
    <div class="card-auth text-center">
        <div class="logo">
            <i class="bi bi-shield-exclamation"></i>
        </div>

        <div class="auth-title">
            {{ __('messages.flash.no_garage_assigned') }}
        </div>

        <p class="auth-subtitle mt-3">
            Hesabınıza heç bir qaraj təyin olunmayıb və ya qaraj
            girişiniz deaktiv edilib. Zəhmət olmasa sistem
            administratoru ilə əlaqə saxlayın.
        </p>

        <div class="alert alert-warning text-start mt-4 mb-4">
            <i class="bi bi-info-circle"></i>
            <strong>Nə edə bilərsiniz?</strong>
            <ul class="mb-0 mt-2">
                <li>Sistem administratorundan qaraj təyinatı istəyin</li>
                <li>Giriş məlumatlarınızın doğru olduğunu yoxlayın</li>
                <li>Hesabınız deaktiv edilibsə, dəstək ilə əlaqə saxlayın</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-register w-100">
                <i class="bi bi-box-arrow-left"></i> {{ __('messages.auth.logout') }}
            </button>
        </form>

        <div class="auth-footer mt-4">
            <a href="{{ url('/') }}">
                <i class="bi bi-house"></i> Ana səhifə
            </a>
        </div>
    </div>
</div>
@endsection
