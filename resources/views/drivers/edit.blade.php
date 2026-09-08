@extends('layouts.app')

@section('title', 'Edit Driver')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>✏️ Edit Driver</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Changes not saved.</strong>
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