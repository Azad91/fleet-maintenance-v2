@extends('layouts.app')

@section('title', __('messages.oil_change.add_change'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-0">🛢️ {{ __('messages.oil_change.add_change') }}</h1>
    </div>
    <a href="{{ route('oil-changes.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('oil-changes.store') }}" method="POST">
            @csrf
            @include('oil-changes.partials.form', ['submitLabel' => __('messages.common.save')])
        </form>
    </div>
</div>
@endsection
