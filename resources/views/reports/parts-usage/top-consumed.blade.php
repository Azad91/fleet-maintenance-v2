@extends('reports.layouts.report-shell')

@section('report-content')
@php $max = $rows->max('total_quantity') ?: 1; @endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>{{ __('messages.complaints.part_code') }}</th>
                        <th>{{ __('messages.complaints.part_name') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.times_used') }}</th>
                        <th style="width: 25%;">{{ __('messages.reports.content.total_quantity') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = round(($row->total_quantity / $max) * 100, 1); @endphp
                        <tr>
                            <td>
                                @if($loop->iteration <= 3)
                                    <span class="fleet-status fleet-status--warning">#{{ $loop->iteration }}</span>
                                @else
                                    <strong>{{ $loop->iteration }}</strong>
                                @endif
                            </td>
                            <td><code>{{ $row->code }}</code></td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ (int) $row->times_used }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                        <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#7c3aed,#a78bfa);"></div>
                                    </div>
                                    <strong style="white-space:nowrap;">{{ number_format($row->total_quantity, 0, ',', '.') }}</strong>
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
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-boxes-stacked fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
