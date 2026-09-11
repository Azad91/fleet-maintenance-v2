@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $maxDistance = $buses->max('distance') ?: 1;
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.start_km') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.end_km') }}</th>
                        <th style="width: 25%;">{{ __('messages.reports.content.distance') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.entries') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buses as $bus)
                        @php $percent = $maxDistance > 0 ? round(($bus->distance / $maxDistance) * 100, 1) : 0; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $bus->dqn }}</strong></td>
                            <td>{{ $bus->route_number ?? '—' }}</td>
                            <td class="text-end">{{ number_format($bus->start_km, 0, ',', '.') }} km</td>
                            <td class="text-end">{{ number_format($bus->end_km, 0, ',', '.') }} km</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                                    </div>
                                    <strong style="white-space: nowrap;">{{ number_format($bus->distance, 0, ',', '.') }} km</strong>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ $bus->entries_count }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-gauge-high fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
