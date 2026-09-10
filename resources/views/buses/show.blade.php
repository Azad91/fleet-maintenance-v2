@extends('layouts.app')

@section('title', __('messages.buses.details_title'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3>{{ __('messages.buses.details_title') }} - #{{ $bus->id }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>{{ __('messages.buses.bus_project') }}</th><td>{{ $bus->bus_project }}</td></tr>
                                <tr><th>{{ __('messages.buses.vin') }}</th><td>{{ $bus->vin }}</td></tr>
                                <tr><th>{{ __('messages.buses.length') }}</th><td>{{ $bus->uzunluq }}</td></tr>
                                <tr><th>{{ __('messages.buses.route_number') }}</th><td>{{ $bus->route_number }}</td></tr>
                                <tr><th>{{ __('messages.buses.dqn') }}</th><td>{{ $bus->dqn }}</td></tr>
                                <tr><th>{{ __('messages.buses.engine_number') }}</th><td>{{ $bus->engine_number }}</td></tr>
                                <tr><th>{{ __('messages.buses.km') }}</th><td>{{ $bus->km }}</td></tr>
                                <tr><th>{{ __('messages.buses.status') }}</th>
                                    <td>{{ $bus->is_active ? __('messages.buses.status_active') : __('messages.buses.status_inactive') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('messages.buses.km_history') }}</h4>
                </div>
                <div class="card-body">
                    @php
                        $dailyKms = $bus->dailyKmRecords()->orderBy('date', 'desc')->get();
                    @endphp

                    @if($dailyKms->count() > 0)
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-striped">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('messages.daily_km.date') }}</th>
                                        <th>{{ __('messages.daily_km.km') }}</th>
                                        <th>{{ __('messages.daily_km.notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyKms as $index => $record)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $record->date ? $record->date->format('d.m.Y') : '' }}</td>
                                            <td>{{ $record->km }}</td>
                                            <td>{{ $record->notes }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">{{ __('messages.buses.no_km_records') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection