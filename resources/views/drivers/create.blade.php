@extends('layouts.app')

@section('title', 'Yeni Sürücü')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ Yeni Sürücü Əlavə Et</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Sürücü əlavə edilmədi.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('drivers.store') }}" method="POST">
            @csrf
            @include('drivers.partials.form')
        </form>
    </div>
</div>
@endsection
