@extends('layouts.guest')

@section('title', 'Fleet Maintenance')

@section('styles')
    {{-- No specific CSS for this page, all from app.css --}}
@endsection

@section('content')
<div class="welcome-container">
    <div class="card-welcome">
        <div class="logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <h1>Fleet <span>Maintenance</span></h1>
        <p class="subtitle">
            The complete solution for managing your bus fleet
        </p>

        <div class="features">
            <div class="feature-item">
                <i class="bi bi-bus-front"></i>
                <h6>Buses</h6>
                <p>All bus information</p>
            </div>
            <div class="feature-item">
                <i class="bi bi-clipboard"></i>
                <h6>Complaints</h6>
                <p>Problems and breakdowns</p>
            </div>
            <div class="feature-item">
                <i class="bi bi-box-seam"></i>
                <h6>Warehouse</h6>
                <p>Spare parts inventory</p>
            </div>
        </div>

        <div class="btn-group-custom">
            <a href="{{ route('login') }}" class="btn-custom btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
        </div>

        <div class="footer-text">
            &copy; {{ date('Y') }} Fleet Maintenance. All rights reserved.
        </div>
    </div>
</div>
@endsection