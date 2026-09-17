@extends('reports.layouts.report-shell')

@section('report-content')
@php
    // Group flat rows (vehicle + code) by vehicle for a cleaner read.
    $grouped = $rows->groupBy('service_vehicle_id');
@endphp

@forelse($grouped as $vehicleId => $vehicleRows)
    @php
        $vehicle = $vehicleRows->first();
        $vehicleTotal = $vehicleRows->sum('total_used');
    @endphp
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>🚐 {{ $vehicle->service_vehicle_name }}</strong>
                @if($vehicle->service_vehicle_plate)
                    <code class="ms-2">{{ $vehicle->service_vehicle_plate }}</code>
                @endif
            </div>
            <span class="badge bg-primary">
                {{ __('messages.reports.content.total_used') }}: {{ number_format($vehicleTotal, 0, ',', '.') }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>{{ __('messages.complaints.part_code') }}</th>
                            <th>{{ __('messages.complaints.part_name') }}</th>
                            <th class="text-center">{{ __('messages.reports.content.times_used') }}</th>
                            <th class="text-end">{{ __('messages.reports.content.total_used') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicleRows as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><code>{{ $row->code }}</code></td>
                                <td><strong>{{ $row->part_name }}</strong></td>
                                <td class="text-center">
                                    <span class="fleet-status fleet-status--muted">{{ $row->times_used }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fleet-status fleet-status--warning">
                                        {{ number_format($row->total_used, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
            {{ __('messages.reports.content.no_data') }}
        </div>
    </div>
@endforelse
@endsection
