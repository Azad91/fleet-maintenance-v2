@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $max = $rows->max('total_used') ?: 1;
    $totalCost = $rows->sum('total_cost');
@endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-list"></i></span>
        <div>
            <span>{{ __('messages.reports.content.distinct_parts') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-money-bill-wave"></i></span>
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
                        <th>{{ __('messages.complaints.part_code') }}</th>
                        <th>{{ __('messages.complaints.part_name') }}</th>
                        <th class="text-center">{{ __('messages.oil_change.interval_km') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.times_used') }}</th>
                        <th style="width: 20%;">{{ __('messages.reports.content.total_used') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = round(($row->total_used / $max) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $row->code }}</code></td>
                            <td><strong>{{ $row->catalog_name ?? $row->part_name }}</strong></td>
                            <td class="text-center">
                                @if($row->catalog_km)
                                    <span class="badge bg-secondary">{{ number_format($row->catalog_km, 0, '', '.') }} km</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ (int) $row->times_used }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                        <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#2563eb,#60a5fa);"></div>
                                    </div>
                                    <strong style="white-space:nowrap;">{{ (int) $row->total_used }}</strong>
                                </div>
                            </td>
                            <td class="text-end">
                                @if($row->total_cost > 0)
                                    <strong>{{ number_format($row->total_cost, 2) }} ₼</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-oil-can fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
