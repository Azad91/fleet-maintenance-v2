@extends('layouts.app')

@section('title', __('messages.transfers.new'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.warehouses') }}</span>
        <h1 class="mb-0">🔁 {{ __('messages.transfers.new') }}</h1>
    </div>
    <a href="{{ route('warehouse-transfers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('warehouse-transfers.store') }}" method="POST">
            @csrf
            @include('warehouse-transfers.partials.form', ['submitLabel' => __('messages.common.save')])
        </form>
    </div>
</div>
@endsection
