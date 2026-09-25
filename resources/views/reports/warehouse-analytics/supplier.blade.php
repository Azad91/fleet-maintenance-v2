@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i>
    {{ __('messages.reports.content.supplier_hint') }}
</div>

@php
    $maxUsed = $rows->max('total_used') ?: 1;
    $totalCost = $rows->sum('total_cost');
@endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-truck"></i></span>
        <div>
            <span>{{ __('messages.warehouse.supplier') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_cost') }}</span>
            <strong>{{ number_format($totalCost, 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
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
                        <th>{{ __('messages.warehouse.supplier') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.distinct_parts') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.cards_opened') }}</th>
                        <th style="width: 22%;">{{ __('messages.reports.content.total_used') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = round(($row->total_used / $maxUsed) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->supplier }}</strong></td>
                            <td class="text-center">{{ (int) $row->distinct_parts }}</td>
                            <td class="text-center">{{ (int) $row->cards_count }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                        <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#2563eb,#60a5fa);"></div>
                                    </div>
                                    <strong style="white-space:nowrap;">{{ (int) $row->total_used }}</strong>
                                </div>
                            </td>
                            <td class="text-end">{{ number_format((float) $row->total_cost, 2) }} ₼</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-truck fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
