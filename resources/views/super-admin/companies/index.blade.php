@extends('layouts.app')

@section('title', __('messages.super_admin.companies.title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
        <h1 class="mb-1">{{ __('messages.super_admin.companies.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.super_admin.companies.subtitle') }}</p>
    </div>
    <a href="{{ route('super-admin.companies.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('messages.super_admin.companies.new') }}
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

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.super_admin.companies.name') }}</th>
                        <th>Slug</th>
                        <th>{{ __('messages.super_admin.companies.email') }}</th>
                        <th class="text-center">{{ __('messages.super_admin.companies.garages_count') }}</th>
                        <th class="text-center">{{ __('messages.super_admin.companies.users_count') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>{{ $companies->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('super-admin.companies.show', $company) }}" class="text-decoration-none">
                                    <strong>{{ $company->name }}</strong>
                                </a>
                            </td>
                            <td><code>{{ $company->slug }}</code></td>
                            <td>{{ $company->email ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $company->garages_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $company->users_count }}</span>
                            </td>
                            <td>
                                @if($company->is_active)
                                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('super-admin.companies.show', $company) }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.common.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('super-admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-warning" title="{{ __('messages.common.edit') }}">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    <form action="{{ route('super-admin.companies.destroy', $company) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.super_admin.companies.delete_confirm') }}')">
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
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-building fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
    {{ $companies->withQueryString()->links() }}
</div>
@endsection
