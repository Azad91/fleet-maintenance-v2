@extends('layouts.app')

@section('title', 'Employee Details')

@section('content')
<div class="container">
    <h1>👤 Employee Details</h1>

    <div class="section-title">📋 Basic Information</div>

    <div class="field">
        <span class="label">ID:</span>
        <span class="value">{{ $employee->id }}</span>
    </div>
    <div class="field">
        <span class="label">First Name:</span>
        <span class="value">{{ $employee->first_name }}</span>
    </div>
    <div class="field">
        <span class="label">Last Name:</span>
        <span class="value">{{ $employee->last_name }}</span>
    </div>
    <div class="field">
        <span class="label">Position:</span>
        <span class="value">{{ $employee->position }}</span>
    </div>

    <div class="section-title">📊 Status</div>
    <div class="field">
        <span class="label">Active:</span>
        <span class="value {{ $employee->is_active ? 'active-yes' : 'active-no' }}">
            {{ $employee->is_active ? '✅ Active' : '❌ Inactive' }}
        </span>
    </div>

    <div class="section-title">📝 Notes</div>
    <div class="field">
        <span class="label">Notes:</span>
        <span class="value">{{ $employee->notes ?? '-' }}</span>
    </div>

    <div class="section-title">📅 Additional Information</div>
    <div class="field">
        <span class="label">Created:</span>
        <span class="value">{{ $employee->created_at ? $employee->created_at->format('d.m.Y H:i') : '-' }}</span>
    </div>
    <div class="field">
        <span class="label">Last Updated:</span>
        <span class="value">{{ $employee->updated_at ? $employee->updated_at->format('d.m.Y H:i') : '-' }}</span>
    </div>

    <br>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>
@endsection