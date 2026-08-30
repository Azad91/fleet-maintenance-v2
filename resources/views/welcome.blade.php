@extends('layouts.guest')

@section('title', 'Fleet Maintenance')

@section('styles')
    {{-- Bu səhifəyə xüsusi CSS yoxdur, hamısı app.css - dən gəlir --}}
@endsection

@section('content')
<div class="welcome-container">
    <div class="card-welcome">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <h1>Fleet <span>Maintenance</span></h1>
        <p class="subtitle">
            Avtobus parkınızın idarə edilməsi üçün tam həll
        </p>

        <div class="features">
            <div class="feature-item">
                <i class="bi bi-bus-front"></i>
                <h6>Avtobuslar</h6>
                <p>Bütün avtobus məlumatları</p>
            </div>
            <div class="feature-item">
                <i class="bi bi-clipboard"></i>
                <h6>Şikayətlər</h6>
                <p>Problem və nasazlıqlar</p>
            </div>
            <div class="feature-item">
                <i class="bi bi-box-seam"></i>
                <h6>Anbar</h6>
                <p>Ehtiyat hissələri</p>
            </div>
        </div>

        <div class="btn-group-custom">
            <a href="{{ route('login') }}" class="btn-custom btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Daxil Ol
            </a>
        </div>

        <div class="footer-text">
            &copy; {{ date('Y') }} Fleet Maintenance. Bütün hüquqlar qorunur.
        </div>
    </div>
</div>
@endsection
