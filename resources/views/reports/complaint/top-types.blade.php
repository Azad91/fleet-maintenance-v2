@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $max = $items->max('total') ?: 1;
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.complaints.complaint_type') }}</th>
                        <th style="width: 45%;">{{ __('messages.reports.content.distribution') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $percent = round(($item->total / $max) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>
                                    {{ $item->complaint_type
                                        ? __('enums.complaint_type.' . $item->complaint_type)
                                        : __('messages.reports.content.unknown_type') }}
                                </strong>
                            </td>
                            <td>
                                <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                    <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $item->total }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
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
