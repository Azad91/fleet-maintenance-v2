@extends('layouts.app')

@section('title', __('messages.employees.details'))

@section('content')
<div class="container">
    <h1>👤 {{ __('messages.employees.details') }}</h1>

    <div class="section-title">📋 {{ __('messages.warehouse.basic_info') }}</div>

    <div class="field">
        <span class="label">ID:</span>
        <span class="value">{{ $employee->id }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.employees.first_name') }}:</span>
        <span class="value">{{ $employee->first_name }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.employees.last_name') }}:</span>
        <span class="value">{{ $employee->last_name }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.employees.position') }}:</span>
        <span class="value">{{ $employee->position }}</span>
    </div>

    <div class="section-title">📊 {{ __('messages.common.status') }}</div>
    <div class="field">
        <span class="label">{{ __('messages.common.active') }}:</span>
        <span class="value {{ $employee->is_active ? 'active-yes' : 'active-no' }}">
            {{ $employee->is_active ? '✅ ' . __('messages.common.active') : '❌ ' . __('messages.common.inactive') }}
        </span>
    </div>

    <div class="section-title">📝 {{ __('messages.warehouse.notes_section') }}</div>
    <div class="field">
        <span class="label">{{ __('messages.common.notes') }}:</span>
        <span class="value">{{ $employee->notes ?? '-' }}</span>
    </div>

    <div class="section-title">📅 {{ __('messages.complaints.info') }}</div>
    <div class="field">
        <span class="label">{{ __('messages.complaints.created') }}:</span>
        <span class="value">{{ $employee->created_at ? $employee->created_at->format('d.m.Y H:i') : '-' }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.complaints.last_updated') }}:</span>
        <span class="value">{{ $employee->updated_at ? $employee->updated_at->format('d.m.Y H:i') : '-' }}</span>
    </div>

    <br>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> {{ __('messages.common.edit') }}
        </a>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
    </div>
</div>
@endsection