@extends('layouts.app')

@section('title', __('messages.oil_change.edit_change'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-0">🛢️ {{ __('messages.oil_change.edit_change') }}</h1>
        <p class="text-muted mb-0">
            {{ $change->bus?->dqn }} · {{ $change->oil_type->label() }}
        </p>
    </div>
    <a href="{{ route('oil-changes.show', $change->bus_id) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('oil-changes.update', $change) }}" method="POST">
            @csrf
            @method('PUT')
            @include('oil-changes.partials.form', ['submitLabel' => __('messages.common.update')])
        </form>
    </div>
</div>
@endsection
