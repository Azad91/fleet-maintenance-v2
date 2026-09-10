@extends('layouts.app')

@section('title', __('messages.users.new'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.administration') }}</span>
        <h1 class="mb-0">{{ __('messages.users.new') }}</h1>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        @include('users.partials.form', ['submitLabel' => __('messages.users.create_button')])
    </div>
</div>
@endsection