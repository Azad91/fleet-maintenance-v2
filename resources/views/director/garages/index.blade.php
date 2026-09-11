@extends('layouts.app')

@section('title', __('messages.director.garages_title'))

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.director.eyebrow') }}</span>
            <h1>{{ __('messages.director.garages_title') }}</h1>
            <p>{{ $company->name }} · {{ __('messages.director.garages_subtitle') }}</p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('director.dashboard') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
        </div>
    </section>

    <section class="fleet-panel">
        <div class="fleet-table-wrap">
            <table class="fleet-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.super_admin.garages.name') }}</th>
                        <th>{{ __('messages.super_admin.garages.code') }}</th>
                        <th>{{ __('messages.super_admin.garages.address') }}</th>
                        <th class="text-center">{{ __('messages.nav.buses') }}</th>
                        <th class="text-center">{{ __('messages.director.complaints') }}</th>
                        <th class="text-center">{{ __('messages.director.warehouses') }}</th>
                        <th class="text-center">{{ __('messages.nav.employees') }}</th>
                        <th class="text-center">{{ __('messages.nav.drivers') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($garages as $garage)
                        <tr>
                            <td><strong>{{ $garage->name }}</strong></td>
                            <td><code>{{ $garage->code }}</code></td>
                            <td>{{ $garage->address ?? '—' }}</td>
                            <td class="text-center">{{ $garage->buses_count }}</td>
                            <td class="text-center">{{ $garage->complaints_count }}</td>
                            <td class="text-center">{{ $garage->warehouses_count }}</td>
                            <td class="text-center">{{ $garage->employees_count }}</td>
                            <td class="text-center">{{ $garage->drivers_count }}</td>
                            <td>
                                @if($garage->is_active)
                                    <span class="fleet-status fleet-status--success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="fleet-status fleet-status--muted">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('director.garages.show', $garage) }}" class="fleet-text-link">
                                    {{ __('messages.common.view') }} <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="fleet-table__empty">{{ __('messages.director.no_garages') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
