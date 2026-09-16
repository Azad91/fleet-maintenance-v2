@extends('layouts.app')

@section('title', __('messages.service_vehicles.new'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
        <h1 class="mb-0">{{ __('messages.service_vehicles.new') }}</h1>
    </div>
    <a href="{{ route('service-vehicles.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('service-vehicles.store') }}" method="POST">
            @csrf
            @include('service-vehicles.partials.form', ['submitLabel' => __('messages.common.save')])
        </form>
    </div>
</div>
@endsection
