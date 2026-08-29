@extends('layouts.app')

@section('title', 'Yeni Kart')

@section('content')
<div class="complaint-create-page">
    <div class="complaint-create-page__heading">
        <div><span class="fleet-eyebrow">TEXNİKİ QEYD</span><h1>Yeni kart aç</h1><p>Avtobus, yer və görülən iş məlumatlarını ardıcıl daxil edin.</p></div>
        <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--secondary"><i class="fas fa-arrow-left"></i> Kartlara qayıt</a>
    </div>
    <div class="card complaint-create-card">
    <div class="card-header">
        <h4><i class="fas fa-screwdriver-wrench"></i> Kart məlumatları</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form id="complaintForm" action="{{ route('complaints.store') }}" method="POST">
            @csrf
            @include('complaints.partials.form')
        </form>
    </div>
</div></div>
@endsection
