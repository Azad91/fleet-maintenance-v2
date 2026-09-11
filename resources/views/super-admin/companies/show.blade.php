@extends('layouts.app')

@section('title', $company->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-0">{{ $company->name }}</h1>
        <p class="text-muted mb-0"><code>{{ $company->slug }}</code></p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('super-admin.companies.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
        <a href="{{ route('super-admin.companies.edit', $company) }}" class="btn btn-warning">
            <i class="fas fa-pencil"></i> {{ __('messages.common.edit') }}
        </a>
    </div>
</div>

{{-- Company Info --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">{{ __('messages.super_admin.companies.show_title') }}</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.super_admin.companies.email') }}</small>
                <strong>{{ $company->email ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.super_admin.companies.phone') }}</small>
                <strong>{{ $company->phone ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.common.status') }}</small>
                @if($company->is_active)
                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                @else
                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                @endif
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('messages.complaints.created') }}</small>
                <strong>{{ $company->created_at->format('d.m.Y H:i') }}</strong>
            </div>
            @if($company->address)
                <div class="col-12">
                    <small class="text-muted d-block">{{ __('messages.super_admin.companies.address') }}</small>
                    <strong>{{ $company->address }}</strong>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Garages --}}
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('messages.super_admin.companies.garages_title') }} ({{ $company->garages->count() }})</h5>
                <a href="{{ route('super-admin.garages.create', ['company_id' => $company->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> {{ __('messages.super_admin.garages.new') }}
                </a>
            </div>
            <div class="card-body p-0">
                @if($company->garages->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('messages.super_admin.garages.name') }}</th>
                                    <th>{{ __('messages.super_admin.garages.code') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th class="text-end">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($company->garages as $garage)
                                    <tr>
                                        <td>
                                            <a href="{{ route('super-admin.garages.show', $garage) }}" class="text-decoration-none">
                                                <strong>{{ $garage->name }}</strong>
                                            </a>
                                        </td>
                                        <td><code>{{ $garage->code }}</code></td>
                                        <td>
                                            @if($garage->is_active)
                                                <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                            @else
                                                <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('super-admin.garages.edit', $garage) }}" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-5">{{ __('messages.super_admin.companies.no_garages') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
