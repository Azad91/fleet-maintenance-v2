@extends('layouts.app')

@section('title', __('messages.oil_change.title'))

@php
    use App\Enums\OilType;
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-1">🛢️ {{ __('messages.oil_change.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.oil_change.subtitle') }}</p>
    </div>
    <a href="{{ route('oil-changes.create', ['type' => $activeType->value]) }}"
       class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('messages.oil_change.new') }}
    </a>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{{ session('success') }}
    </div>
@endif

{{-- Type tabs --}}
<ul class="nav nav-tabs mb-3">
    @foreach(OilType::cases() as $type)
        <li class="nav-item">
            <a class="nav-link {{ $activeType === $type ? 'active' : '' }}"
               href="{{ route('oil-changes.index', ['type' => $type->value]) }}">
                {{ $type->icon() }} {{ $type->label() }}
            </a>
        </li>
    @endforeach
</ul>

@include('oil-changes.partials.table', ['statuses' => $statuses])
@endsection
