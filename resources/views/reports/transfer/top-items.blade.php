@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $max = $items->max('times_transferred') ?: 1;
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.warehouse.code') }}</th>
                        <th>{{ __('messages.warehouse.name') }}</th>
                        <th>{{ __('messages.warehouse.unit') }}</th>
                        <th class="text-center">{{ __('messages.transfers.report.times_transferred') }}</th>
                        <th class="text-end">{{ __('messages.transfers.report.total_declared') }}</th>
                        <th class="text-end">{{ __('messages.transfers.report.total_received') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $percent = round(($item->times_transferred / $max) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->unit ?? '—' }}</td>
                            <td class="text-center">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                                    </div>
                                    <strong style="min-width: 40px; text-align: right;">{{ $item->times_transferred }}</strong>
                                </div>
                            </td>
                            <td class="text-end">{{ number_format($item->total_declared, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($item->total_received, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
