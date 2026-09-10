@extends('layouts.app')

@section('title', __('messages.drivers.new'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ {{ __('messages.drivers.new') }}</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>{{ __('messages.drivers.not_added') }}</strong>
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