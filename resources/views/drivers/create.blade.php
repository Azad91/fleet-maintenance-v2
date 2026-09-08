@extends('layouts.app')

@section('title', 'New Driver')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ Add New Driver</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Driver not added.</strong>
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