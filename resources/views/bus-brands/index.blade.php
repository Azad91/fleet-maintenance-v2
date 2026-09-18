@extends('layouts.app')

@section('title', __('messages.bus_brands.title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
        <h1 class="mb-1">🏷️ {{ __('messages.bus_brands.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.bus_brands.subtitle') }}</p>
    </div>
    <a href="{{ route('bus-brands.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('messages.bus_brands.new') }}
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
                        <th>{{ __('messages.bus_brands.name') }}</th>
                        <th>{{ __('messages.bus_brands.code') }}</th>
                        <th class="text-center">{{ __('messages.bus_brands.buses_count') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                        <tr>
                            <td>{{ $brands->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $brand->name }}</strong></td>
                            <td><code>{{ $brand->code }}</code></td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $brand->buses_count }}</span>
                            </td>
                            <td>
                                @if($brand->is_active)
                                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('bus-brands.edit', $brand) }}"
                                       class="btn btn-sm btn-outline-warning"
                                       title="{{ __('messages.common.edit') }}">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    <form action="{{ route('bus-brands.destroy', $brand) }}"
                                          method="POST"
                                          style="display:inline"
                                          onsubmit="return confirm('{{ __('messages.bus_brands.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                title="{{ __('messages.common.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-tag fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.bus_brands.no_brands') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($brands->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $brands->withQueryString()->links() }}
    </div>
@endif
@endsection
