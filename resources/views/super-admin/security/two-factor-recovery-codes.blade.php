@extends('layouts.app')

@section('title', __('messages.two_factor.recovery_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ __('messages.two_factor.recovery_title') }}</h1>
    </div>
</div>

<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    {{ __('messages.two_factor.recovery_warning') }}
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">
            <i class="fas fa-key"></i> {{ __('messages.two_factor.recovery_codes') }}
        </h5>

        <div class="row">
            @foreach($codes as $code)
                <div class="col-md-6 mb-2">
                    <div class="p-3 bg-light rounded text-center">
                        <code style="font-size: 16px; letter-spacing: 1px;">{{ $code }}</code>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('super-admin.settings.index') }}" class="btn btn-primary">
                <i class="fas fa-check"></i> {{ __('messages.two_factor.saved_codes') }}
            </a>
        </div>
    </div>
</div>
@endsection
