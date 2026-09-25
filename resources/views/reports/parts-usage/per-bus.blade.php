@extends('reports.layouts.report-shell')

@section('report-content')
@php $maxCost = $rows->max('total_cost') ?: 1; @endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.cards_opened') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.distinct_parts') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_quantity') }}</th>
                        <th style="width: 20%;">{{ __('messages.reports.content.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = round(($row->total_cost / $maxCost) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->dqn }}</strong></td>
                            <td>{{ $row->route_number ?? '—' }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ (int) $row->cards_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ (int) $row->distinct_parts }}</span>
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($row->total_quantity, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                        <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#f59e0b,#fbbf24);"></div>
                                    </div>
                                    <strong style="white-space:nowrap;">{{ number_format($row->total_cost, 2) }} ₼</strong>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-puzzle-piece fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
