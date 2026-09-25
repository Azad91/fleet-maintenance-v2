@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i>
    {{ __('messages.reports.content.reorder_hint') }}
</div>

@php $totalCost = $rows->sum('estimated_cost'); @endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-cart-plus"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.items') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.estimated_cost') }}</span>
            <strong>{{ number_format($totalCost, 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.total_cost') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.warehouse.code') }}</th>
                        <th>{{ __('messages.warehouse.name') }}</th>
                        <th class="text-center">{{ __('messages.warehouse.quantity') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.min_quantity') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.suggested_order') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.estimated_cost') }}</th>
                        <th>{{ __('messages.warehouse.supplier') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $row->code }}</code></td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ (int) $row->quantity }}</span>
                            </td>
                            <td class="text-center">{{ (int) $row->minimum_quantity }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning" style="font-size:14px;">
                                    <strong>+{{ (int) $row->suggested_order }}</strong>
                                </span>
                            </td>
                            <td class="text-end">
                                {{ number_format($row->estimated_cost, 2) }} ₼
                            </td>
                            <td>{{ $row->supplier ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-circle-check fa-2x mb-3 d-block" style="color:#10b981; opacity:.5;"></i>
                                {{ __('messages.reports.content.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
