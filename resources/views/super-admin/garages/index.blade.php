@extends('layouts.app')

@section('title', __('messages.super_admin.garages.title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-1">{{ __('messages.super_admin.garages.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.super_admin.garages.subtitle') }}</p>
    </div>
    <a href="{{ route('super-admin.garages.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('messages.super_admin.garages.new') }}
    </a>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="fleet-alert fleet-alert--error">
        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
    </div>
@endif

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('super-admin.garages.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="company_id" class="form-label fw-bold">{{ __('messages.super_admin.garages.filter_company') }}</label>
                <select name="company_id" id="company_id" class="form-select">
                    <option value="">{{ __('messages.super_admin.garages.all_companies') }}</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-bold">{{ __('messages.super_admin.garages.filter_status') }}</label>
                <select name="status" id="status" class="form-select">
                    <option value="">{{ __('messages.super_admin.garages.all_statuses') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('messages.super_admin.garages.status_active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.super_admin.garages.status_inactive') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> {{ __('messages.common.filter') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.super_admin.garages.name') }}</th>
                        <th>{{ __('messages.super_admin.garages.code') }}</th>
                        <th>{{ __('messages.super_admin.garages.company') }}</th>
                        <th class="text-center">{{ __('messages.super_admin.garages.users_count') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($garages as $garage)
                        <tr>
                            <td>{{ $garages->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('super-admin.garages.show', $garage) }}" class="text-decoration-none">
                                    <strong>{{ $garage->name }}</strong>
                                </a>
                            </td>
                            <td><code>{{ $garage->code }}</code></td>
                            <td>
                                @if($garage->company)
                                    <a href="{{ route('super-admin.companies.show', $garage->company) }}" class="text-decoration-none">
                                        {{ $garage->company->name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $garage->users_count }}</span>
                            </td>
                            <td>
                                @if($garage->is_active)
                                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('super-admin.garages.show', $garage) }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.common.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('super-admin.garages.edit', $garage) }}" class="btn btn-sm btn-outline-warning" title="{{ __('messages.common.edit') }}">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    <form action="{{ route('super-admin.garages.destroy', $garage) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.super_admin.garages.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.common.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-warehouse fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.common.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $garages->withQueryString()->links() }}
</div>
@endsection
