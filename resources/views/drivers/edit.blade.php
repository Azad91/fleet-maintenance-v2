@extends('layouts.app')

@section('title', 'Sürücü Redaktə Et')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ Sürücü Redaktə Et</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Dəyişiklik yadda saxlanmadı.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('drivers.update', $driver) }}" method="POST">
            @csrf
            @method('PUT')
            @include('drivers.partials.form')
        </form>
    </div>
</div>
@endsection
