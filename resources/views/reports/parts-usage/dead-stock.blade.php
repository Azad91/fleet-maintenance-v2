@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-warning mb-3">
    <i class="fas fa-triangle-exclamation"></i>
    {{ __('messages.reports.content.dead_stock_hint') }}
</div>

@php $totalCapital = $items->sum('tied_capital'); @endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-boxes-stacked"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total') }}</span>
            <strong>{{ $items->count() }}</strong>
            <small>{{ __('messages.reports.content.items') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.tied_capital') }}</span>
            <strong>{{ number_format($totalCapital, 2) }} ₼</strong>
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
                        <th>{{ __('messages.warehouse.unit') }}</th>
                        <th class="text-end">{{ __('messages.warehouse.price') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.tied_capital') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td>{{ $item->unit ?? '—' }}</td>
                            <td class="text-end">{{ $item->price ? number_format($item->price, 2) . ' ₼' : '—' }}</td>
                            <td class="text-end">
                                <strong style="color:#dc2626;">{{ number_format($item->tied_capital, 2) }} ₼</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-circle-check fa-2x mb-3 d-block" style="opacity:.3;"></i>
                                {{ __('messages.reports.content.no_dead_stock') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
